<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostelBooking;
use App\Models\Session;
use App\Models\Student;
use App\Models\HostelRoom;
use App\Models\Hostel;
use App\Models\HostelFee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HostelBookingController extends Controller
{
    public function index(Request $request)
    {
        $currentSession = Session::current();
        $sessionId = $request->input('session_id', $currentSession?->id);

        $bookings = HostelBooking::with([
            'student.user',
            'student.department',
            'room.floor.block.hostel',
            'session',
            'invoice.payments'
        ])
            ->when($sessionId, function ($query, $sessionId) {
                $query->where('session_id', $sessionId);
            })
            ->latest()
            ->get();

        $sessions = Session::latest()->get(['id', 'name']);

        return Inertia::render('Admin/Hostels/Bookings', [
            'bookings' => $bookings,
            'sessions' => $sessions,
            'filters' => [
                'session_id' => $sessionId,
            ],
        ]);
    }

    public function searchStudents(Request $request)
    {
        $search = $request->input('query');

        if (empty($search)) {
            return response()->json([]);
        }

        $students = \App\Models\User::role('student')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($s) use ($search) {
                        $s->where('matriculation_number', 'like', "%{$search}%");
                    });
            })
            ->with([
                'student' => function ($q) {
                    $q->select('id', 'user_id', 'matriculation_number', 'department_id', 'current_level', 'gender');
                }
            ])
            ->limit(15)
            ->get(['id', 'name', 'email']);

        return response()->json($students);
    }

    public function getAvailableRooms(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);
        $studentGender = strtolower($student->gender ?? '');

        $hostels = Hostel::with(['blocks.floors.rooms.bookings'])
            ->when($studentGender, function ($q) use ($studentGender) {
                $q->whereIn('gender_type', [$studentGender, 'mixed']);
            }, function ($q) {
                $q->where('gender_type', 'mixed');
            })
            ->get();

        $formattedHostels = $hostels->map(function ($hostel) {
            return [
                'id' => $hostel->id,
                'name' => $hostel->name,
                'gender_type' => $hostel->gender_type,
                'blocks' => $hostel->blocks->map(function ($block) {
                    return [
                        'id' => $block->id,
                        'name' => $block->name,
                        'floors' => $block->floors->map(function ($floor) {
                            return [
                                'id' => $floor->id,
                                'name' => $floor->name,
                                'rooms' => $floor->rooms->map(function ($room) {
                                    $bookedCount = $room->bookings->whereIn('status', ['pending', 'confirmed'])->count();
                                    $availableBeds = max(0, $room->capacity - $bookedCount);
                                    return [
                                        'id' => $room->id,
                                        'room_number' => $room->room_number,
                                        'capacity' => $room->capacity,
                                        'available_beds' => $availableBeds,
                                        'is_full' => $availableBeds <= 0,
                                    ];
                                })->filter(fn($room) => !$room['is_full'])->values()
                            ];
                        })->filter(fn($floor) => count($floor['rooms']) > 0)->values()
                    ];
                })->filter(fn($block) => count($block['floors']) > 0)->values()
            ];
        })->filter(fn($hostel) => count($hostel['blocks']) > 0)->values();

        return response()->json($formattedHostels);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'hostel_room_id' => 'required|exists:hostel_rooms,id',
            'mark_as_paid' => 'nullable|boolean',
        ]);

        $student = Student::findOrFail($request->student_id);
        $room = HostelRoom::with('floor.block.hostel')->findOrFail($request->hostel_room_id);
        $currentSession = Session::current();

        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // 1. School fee check
        $hasPaidFees = Invoice::where('user_id', $student->user_id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        if (!$hasPaidFees) {
            return back()->with('error', 'Cannot allocate room. This student has not paid their school fees for the current session.');
        }

        // 2. Check existing bookings
        $existingBooking = HostelBooking::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->first();

        if ($existingBooking) {
            return back()->with('error', 'This student already has an active booking for this session.');
        }

        // 3. Room capacity check
        $bookedCount = $room->bookings()->whereIn('status', ['pending', 'confirmed'])->count();
        if ($bookedCount >= $room->capacity) {
            return back()->with('error', 'This room is already fully booked.');
        }

        DB::beginTransaction();
        try {
            $hostelId = $room->floor->block->hostel->id;
            $fee = HostelFee::where('session_id', $currentSession->id)
                ->where(function ($q) use ($hostelId) {
                    $q->where('hostel_id', $hostelId)->orWhereNull('hostel_id');
                })
                ->orderBy('hostel_id', 'desc')
                ->first();

            if (!$fee) {
                throw new \Exception('Accommodation fees have not been configured for this session.');
            }

            $discountAmount = 0;
            $student->load('scholarship');
            if ($student->scholarship && $student->scholarship->covers_hostel_fees) {
                $discountAmount = $fee->amount * ($student->scholarship->percentage / 100);
            }
            $finalAmount = $fee->amount - $discountAmount;

            $reference = 'HST-' . strtoupper(uniqid());
            $isPaid = $request->input('mark_as_paid', false);

            $invoice = Invoice::create([
                'user_id' => $student->user_id,
                'session_id' => $currentSession->id,
                'reference' => $reference,
                'type' => 'hostel_fee',
                'amount' => $finalAmount,
                'status' => $isPaid ? 'paid' : 'pending',
                'paid_amount' => $isPaid ? $finalAmount : 0,
                'due_date' => now()->addDays(7),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Hostel Accommodation Fee ({$room->floor->block->hostel->name} - Block: {$room->floor->block->name}, Room: {$room->room_number})",
                'amount' => $fee->amount,
            ]);

            if ($discountAmount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => "Scholarship Discount ({$student->scholarship->name} - " . floatval($student->scholarship->percentage) . "%)",
                    'amount' => -$discountAmount,
                ]);
            }

            if ($isPaid) {
                Payment::create([
                    'transaction_id' => 'TRX-' . strtoupper(uniqid()),
                    'invoice_id' => $invoice->id,
                    'user_id' => $invoice->user_id,
                    'recorded_by' => Auth::id(),
                    'gateway_reference' => 'MANUAL-' . strtoupper(uniqid()),
                    'amount' => $finalAmount,
                    'status' => 'success',
                    'channel' => 'manual',
                    'paid_at' => now(),
                ]);
            }

            HostelBooking::create([
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
                'hostel_room_id' => $room->id,
                'invoice_id' => $invoice->id,
                'status' => $isPaid ? 'confirmed' : 'pending',
            ]);

            DB::commit();
            return back()->with('success', 'Hostel room allocated successfully for the student!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to allocate hostel: ' . $e->getMessage());
        }
    }
}
