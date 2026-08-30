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
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HostelBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Authorization check
        if (!$user->can('manage_hostel_bookings') &&
            !$user->can('manage_hostels') &&
            !$user->can('view_hostel_bookings') &&
            !$user->can('view_male_hostel_bookings') &&
            !$user->can('view_female_hostel_bookings') &&
            !$user->hasRole('admin')) {
            abort(403, 'Unauthorized access to hostel booking records.');
        }

        $currentSession = Session::current();
        
        $sessionId = $request->input('session_id');
        if ($sessionId === 'all') {
            $sessionId = null;
        } elseif (is_null($sessionId) && !$request->has('session_id')) {
            $sessionId = $currentSession?->id;
        }

        $level = $request->input('level');
        if ($level === 'all') $level = null;

        $hostelId = $request->input('hostel_id');
        if ($hostelId === 'all') $hostelId = null;

        $status = $request->input('status');
        if ($status === 'all') $status = null;

        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $gender = $request->input('gender', 'all');

        // Force gender scope if user has specific male/female supervisor permissions
        if (($user->can('view_male_hostel_bookings') || $user->hasRole('male_hostel_supervisor')) && !$user->can('manage_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            $gender = 'male';
        } elseif (($user->can('view_female_hostel_bookings') || $user->hasRole('female_hostel_supervisor')) && !$user->can('manage_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            $gender = 'female';
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $query = HostelBooking::with([
            'student.user',
            'student.department',
            'room.floor.block.hostel',
            'session',
            'invoice.payments',
            'creator',
            'updater'
        ]);

        // Filters
        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        
        if ($level) {
            $query->whereHas('student', function ($q) use ($level) {
                $q->where('current_level', $level);
            });
        }
        
        if ($hostelId) {
            $query->whereHas('room.floor.block', function ($q) use ($hostelId) {
                $q->where('hostel_id', $hostelId);
            });
        }
        
        if ($status === 'expired') {
            if ($currentSession) {
                $query->where('session_id', '!=', $currentSession->id)
                    ->whereIn('status', ['pending', 'confirmed']);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($status) {
            $query->where('status', $status);
        }
        
        if ($date) {
            $query->whereDate('hostel_bookings.created_at', $date);
        }

        if ($startDate) {
            $query->whereDate('hostel_bookings.created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->whereDate('hostel_bookings.created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($gender === 'male' || $gender === 'female') {
            $query->whereHas('room.floor.block.hostel', function ($q) use ($gender) {
                $q->where('gender_type', $gender);
            });
        }

        // Sorting
        if ($sortBy === 'student_name') {
            $query->join('students', 'hostel_bookings.student_id', '=', 'students.id')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('hostel_bookings.*');
        } elseif ($sortBy === 'hostel_name') {
            $query->join('hostel_rooms', 'hostel_bookings.hostel_room_id', '=', 'hostel_rooms.id')
                ->join('hostel_floors', 'hostel_rooms.hostel_floor_id', '=', 'hostel_floors.id')
                ->join('hostel_blocks', 'hostel_floors.hostel_block_id', '=', 'hostel_blocks.id')
                ->join('hostels', 'hostel_blocks.hostel_id', '=', 'hostels.id')
                ->orderBy('hostels.name', $sortDirection)
                ->select('hostel_bookings.*');
        } else {
            $query->orderBy('hostel_bookings.' . $sortBy, $sortDirection);
        }

        $perPage = $request->integer('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $bookings = $query->paginate($perPage)->withQueryString();

        $sessions = Session::latest()->get(['id', 'name']);
        
        // Scope Hostel Dropdown Options based on permission
        $hostelsQuery = Hostel::orderBy('name');
        if ($gender === 'male' || $gender === 'female') {
            $hostelsQuery->where('gender_type', $gender);
        }
        $hostels = $hostelsQuery->get(['id', 'name', 'gender_type']);

        // Scoped Analytics Calculations
        $statsQuery = HostelBooking::query();
        if ($sessionId) {
            $statsQuery->where('session_id', $sessionId);
        }
        if ($level) {
            $statsQuery->whereHas('student', function ($q) use ($level) {
                $q->where('current_level', $level);
            });
        }
        if ($hostelId) {
            $statsQuery->whereHas('room.floor.block', function ($q) use ($hostelId) {
                $q->where('hostel_id', $hostelId);
            });
        }
        if ($status === 'expired') {
            if ($currentSession) {
                $statsQuery->where('session_id', '!=', $currentSession->id)
                    ->whereIn('status', ['pending', 'confirmed']);
            } else {
                $statsQuery->whereRaw('1 = 0');
            }
        } elseif ($status) {
            $statsQuery->where('status', $status);
        }
        if ($date) {
            $statsQuery->whereDate('created_at', $date);
        }
        if ($startDate) {
            $statsQuery->whereDate('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $statsQuery->whereDate('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }
        if ($gender === 'male' || $gender === 'female') {
            $statsQuery->whereHas('room.floor.block.hostel', function ($q) use ($gender) {
                $q->where('gender_type', $gender);
            });
        }

        $totalBookingsCount = (clone $statsQuery)->count();
        $confirmedCount = (clone $statsQuery)->where('status', 'confirmed')->count();
        $pendingCount = (clone $statsQuery)->where('status', 'pending')->count();
        $cancelledCount = (clone $statsQuery)->where('status', 'cancelled')->count();

        // Capacity for scoped hostels
        $capacityQuery = HostelRoom::query();
        if ($hostelId) {
            $capacityQuery->whereHas('floor.block', function ($q) use ($hostelId) {
                $q->where('hostel_id', $hostelId);
            });
        } elseif ($gender === 'male' || $gender === 'female') {
            $capacityQuery->whereHas('floor.block.hostel', function ($q) use ($gender) {
                $q->where('gender_type', $gender);
            });
        }
        $totalCapacity = (int) $capacityQuery->sum('capacity');
        $availableRooms = max(0, $totalCapacity - $confirmedCount);
        $occupancyRate = $totalCapacity > 0 ? round(($confirmedCount / $totalCapacity) * 100, 1) : 0;

        // Financial Metrics (Paid & Outstanding Balance)
        $bookingInvoiceIds = (clone $statsQuery)->whereNotNull('invoice_id')->pluck('invoice_id');
        
        $totalInvoiceAmount = (float) Invoice::whereIn('id', $bookingInvoiceIds)->sum('amount');
        $totalPaid = (float) Payment::whereIn('invoice_id', $bookingInvoiceIds)
            ->where('status', 'successful')
            ->sum('amount');
        
        $totalBalance = max(0, $totalInvoiceAmount - $totalPaid);

        $genderBreakdown = [
            'male' => (clone $statsQuery)->whereHas('student', fn($q) => $q->where('gender', 'male'))->count(),
            'female' => (clone $statsQuery)->whereHas('student', fn($q) => $q->where('gender', 'female'))->count(),
        ];

        $canManageBookings = $user->can('manage_hostel_bookings') || $user->can('manage_hostels') || $user->hasRole('admin');

        return Inertia::render('Admin/Hostels/Bookings', [
            'bookings' => $bookings,
            'sessions' => $sessions,
            'hostels' => $hostels,
            'stats' => [
                'total_bookings' => $totalBookingsCount,
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'cancelled' => $cancelledCount,
                'total_capacity' => $totalCapacity,
                'available_rooms' => $availableRooms,
                'occupancy_rate' => $occupancyRate,
                'total_paid' => $totalPaid,
                'total_balance' => $totalBalance,
                'gender_breakdown' => $genderBreakdown,
            ],
            'filters' => [
                'session_id' => $sessionId,
                'level' => $level,
                'hostel_id' => $hostelId,
                'status' => $status,
                'date' => $date,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'gender' => $gender,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage,
            ],
            'canManageBookings' => $canManageBookings,
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

        $currentSession = Session::current();
        $currentSessionId = $currentSession ? $currentSession->id : null;

        $hostels = Hostel::with(['blocks.floors.rooms.bookings' => function ($q) use ($currentSessionId) {
            $q->where('session_id', $currentSessionId);
        }])
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
        $user = Auth::user();
        if (!$user->can('manage_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            return back()->with('error', 'Unauthorized. You do not have permission to allocate hostel rooms.');
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'hostel_room_id' => 'required|exists:hostel_rooms,id',
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

        // 2. Check existing active bookings
        $existingBooking = HostelBooking::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingBooking) {
            return back()->with('error', 'This student already has an active accommodation booking for this session.');
        }

        // 3. Room capacity check for current session
        $bookedCount = $room->bookings()
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
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
                if ($student->scholarship->type === 'fixed') {
                    $discountAmount = min($student->scholarship->amount, $fee->amount);
                } else {
                    $discountAmount = $fee->amount * ($student->scholarship->percentage / 100);
                }
            }
            $finalAmount = $fee->amount - $discountAmount;
            $isPaidInput = false;

            // Check if there is an existing hostel fee invoice for this session
            $invoice = Invoice::where('user_id', $student->user_id)
                ->where('session_id', $currentSession->id)
                ->where('type', 'hostel_fee')
                ->first();

            if ($invoice) {
                // Check if invoice is already paid or marked as paid
                $isPaid = in_array($invoice->status, ['paid', 'partial']) || $isPaidInput;
                
                // Clear old items and recreate with new room details
                $invoice->items()->delete();
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => "Hostel Accommodation Fee ({$room->floor->block->hostel->name} - Block: {$room->floor->block->name}, Room: {$room->room_number})",
                    'amount' => $fee->amount,
                ]);

                if ($discountAmount > 0) {
                    $discountDesc = $student->scholarship->type === 'fixed'
                        ? "Scholarship Discount ({$student->scholarship->name} - Fixed ₦" . number_format($student->scholarship->amount, 2) . ")"
                        : "Scholarship Discount ({$student->scholarship->name} - " . floatval($student->scholarship->percentage) . "%)";
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $discountDesc,
                        'amount' => -$discountAmount,
                    ]);
                }

                if ($isPaidInput && !in_array($invoice->status, ['paid', 'partial'])) {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_amount' => $finalAmount,
                        'amount' => $finalAmount,
                    ]);

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
                } elseif (!$isPaid) {
                    $invoice->update(['amount' => $finalAmount]);
                }
                
                $bookingStatus = $isPaid ? 'confirmed' : 'pending';
            } else {
                $reference = 'HST-' . strtoupper(uniqid());
                $isPaid = $isPaidInput;

                $expiryDays = intval(SystemSetting::get('hostel_booking_expiry_days', 2));

                $invoice = Invoice::create([
                    'user_id' => $student->user_id,
                    'session_id' => $currentSession->id,
                    'reference' => $reference,
                    'type' => 'hostel_fee',
                    'amount' => $finalAmount,
                    'status' => $isPaid ? 'paid' : 'pending',
                    'paid_amount' => $isPaid ? $finalAmount : 0,
                    'due_date' => now()->addDays($expiryDays),
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => "Hostel Accommodation Fee ({$room->floor->block->hostel->name} - Block: {$room->floor->block->name}, Room: {$room->room_number})",
                    'amount' => $fee->amount,
                ]);

                if ($discountAmount > 0) {
                    $discountDesc = $student->scholarship->type === 'fixed'
                        ? "Scholarship Discount ({$student->scholarship->name} - Fixed ₦" . number_format($student->scholarship->amount, 2) . ")"
                        : "Scholarship Discount ({$student->scholarship->name} - " . floatval($student->scholarship->percentage) . "%)";
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $discountDesc,
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
                
                $bookingStatus = $isPaid ? 'confirmed' : 'pending';
            }

            $newBooking = HostelBooking::create([
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
                'hostel_room_id' => $room->id,
                'invoice_id' => $invoice->id,
                'status' => $bookingStatus,
            ]);

            activity('hostel')
                ->performedOn($newBooking)
                ->causedBy(Auth::user())
                ->withProperties([
                    'student_name' => $student->user?->name,
                    'matric_number' => $student->matric_number,
                    'hostel' => $room->floor->block->hostel->name,
                    'room_number' => $room->room_number,
                    'status' => $bookingStatus,
                ])
                ->log("Allocated room {$room->room_number} ({$room->floor->block->hostel->name}) to student {$student->user?->name}");

            DB::commit();
            return back()->with('success', 'Hostel room allocated successfully for the student!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to allocate hostel: ' . $e->getMessage());
        }
    }

    public function unbook(HostelBooking $booking)
    {
        $user = Auth::user();
        if (!$user->can('manage_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            return back()->with('error', 'Unauthorized. You do not have permission to unbook or cancel student hostel allocations.');
        }

        $booking->update([
            'status' => 'cancelled',
            'updated_by' => Auth::id(),
        ]);

        activity('hostel')
            ->performedOn($booking)
            ->causedBy(Auth::user())
            ->withProperties([
                'student_name' => $booking->student?->user?->name,
                'hostel' => $booking->room?->floor?->block?->hostel?->name,
                'room_number' => $booking->room?->room_number,
            ])
            ->log("Cancelled hostel allocation for student {$booking->student?->user?->name}");

        return back()->with('success', 'Student unbooked successfully. Room capacity has been released.');
    }

    public function reallocate(HostelBooking $booking)
    {
        $user = Auth::user();
        if (!$user->can('manage_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            return back()->with('error', 'Unauthorized. You do not have permission to reallocate hostel rooms.');
        }

        $room = $booking->room;
        $currentSession = Session::current();
        
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // Room capacity check for current session
        $bookedCount = $room->bookings()
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
            
        if ($bookedCount >= $room->capacity) {
            return back()->with('error', 'Cannot re-allocate. This room is already fully booked.');
        }

        // Check if student already has another active booking for this session
        $activeBookingExists = HostelBooking::where('student_id', $booking->student_id)
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($activeBookingExists) {
            return back()->with('error', 'This student already has another active hostel booking for this session.');
        }

        // If invoice is fully or partially paid, set as confirmed, otherwise pending
        $invoiceStatus = $booking->invoice ? $booking->invoice->status : 'unpaid';
        $newStatus = in_array($invoiceStatus, ['paid', 'partial']) ? 'confirmed' : 'pending';

        $booking->update([
            'status' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        activity('hostel')
            ->performedOn($booking)
            ->causedBy(Auth::user())
            ->withProperties([
                'student_name' => $booking->student?->user?->name,
                'hostel' => $booking->room?->floor?->block?->hostel?->name,
                'room_number' => $booking->room?->room_number,
                'status' => $newStatus,
            ])
            ->log("Re-allocated room {$booking->room?->room_number} to student {$booking->student?->user?->name}");

        return back()->with('success', 'Student room allocation reactivated successfully!');
    }

    public function downloadSlip(HostelBooking $booking)
    {
        $booking->load(['room.floor.block.hostel', 'student.user', 'student.faculty', 'student.department', 'session', 'invoice']);
        
        if ($booking->status !== 'confirmed' || ! $booking->invoice || ! in_array($booking->invoice->status, ['paid', 'partial'])) {
            return back()->with('error', 'Accommodation slip can only be downloaded once the accommodation payment is confirmed.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.accommodation_slip', [
            'booking' => $booking,
            'student' => $booking->student,
            'session' => $booking->session,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf->download("Accommodation_Slip_{$booking->student->matric_no}.pdf");
    }
}
