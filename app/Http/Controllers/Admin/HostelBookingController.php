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

        $blockId = $request->input('block_id');
        if ($blockId === 'all') $blockId = null;

        $floorId = $request->input('floor_id');
        if ($floorId === 'all') $floorId = null;

        $roomId = $request->input('room_id');
        if ($roomId === 'all') $roomId = null;

        $status = $request->input('status');
        if ($status === 'all') $status = null;

        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $gender = $request->input('gender', 'all');

        // Force gender scope if user has specific male/female supervisor permissions
        $userPermittedGender = null;
        $isMaleSupervisor = ($user->can('view_male_hostel_bookings') || $user->hasRole('male_hostel_supervisor')) &&
                            !($user->can('view_female_hostel_bookings') || $user->hasRole('female_hostel_supervisor'));
        $isFemaleSupervisor = ($user->can('view_female_hostel_bookings') || $user->hasRole('female_hostel_supervisor')) &&
                              !($user->can('view_male_hostel_bookings') || $user->hasRole('male_hostel_supervisor'));
        $isGeneralAdmin = $user->can('manage_hostel_bookings') || $user->can('manage_hostels') || $user->hasRole('admin');

        if ($isMaleSupervisor && !$isGeneralAdmin) {
            $userPermittedGender = 'male';
            $gender = 'male';
        } elseif ($isFemaleSupervisor && !$isGeneralAdmin) {
            $userPermittedGender = 'female';
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

        if ($blockId) {
            $query->whereHas('room.floor', function ($q) use ($blockId) {
                $q->where('hostel_block_id', $blockId);
            });
        }

        if ($floorId) {
            $query->whereHas('room', function ($q) use ($floorId) {
                $q->where('hostel_floor_id', $floorId);
            });
        }

        if ($roomId) {
            $query->where('hostel_room_id', $roomId);
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
        $hostelsQuery = Hostel::with(['blocks.floors.rooms'])->orderBy('name');
        if ($gender === 'male' || $gender === 'female') {
            $hostelsQuery->where('gender_type', $gender);
        }
        $hostels = $hostelsQuery->get();

        // Analytics Calculations (Independent of UI request search/filters, but strictly scoped if user has single-gender permission)
        $statsQuery = HostelBooking::query();
        if ($userPermittedGender) {
            $statsQuery->whereHas('room.floor.block.hostel', fn($q) => $q->where('gender_type', $userPermittedGender));
        }

        $totalBookingsCount = (clone $statsQuery)->count();
        $confirmedCount = (clone $statsQuery)->where('status', 'confirmed')->count();
        $pendingCount = (clone $statsQuery)->where('status', 'pending')->count();
        $cancelledCount = (clone $statsQuery)->where('status', 'cancelled')->count();

        // Rooms and Capacity for hostels
        $capacityQuery = HostelRoom::query();
        if ($userPermittedGender) {
            $capacityQuery->whereHas('floor.block.hostel', fn($q) => $q->where('gender_type', $userPermittedGender));
        }

        $totalRooms = (clone $capacityQuery)->count();
        $totalCapacity = (int) (clone $capacityQuery)->sum('capacity');

        // Occupied Rooms: Rooms with at least 1 active booking (pending or confirmed)
        $occupiedRooms = (clone $capacityQuery)->whereHas('bookings', function ($q) {
            $q->whereIn('status', ['pending', 'confirmed']);
        })->count();

        $vacantRooms = max(0, $totalRooms - $occupiedRooms);
        $roomOccupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        $availableBeds = max(0, $totalCapacity - $confirmedCount);
        $occupancyRate = $totalCapacity > 0 ? round(($confirmedCount / $totalCapacity) * 100, 1) : 0;

        // Financial Metrics (Paid & Outstanding Balance)
        $bookingInvoiceIds = (clone $statsQuery)->whereNotNull('invoice_id')->pluck('invoice_id');
        
        $totalInvoiceAmount = (float) Invoice::whereIn('id', $bookingInvoiceIds)->sum('amount');
        $totalPaid = (float) Payment::whereIn('invoice_id', $bookingInvoiceIds)
            ->whereIn('status', ['successful', 'success', 'paid'])
            ->sum('amount');
        
        $totalBalance = max(0, $totalInvoiceAmount - $totalPaid);

        $genderBreakdown = [
            'male' => (clone $statsQuery)->where(function ($q) {
                $q->whereHas('student', fn($sq) => $sq->whereRaw('LOWER(gender) = ?', ['male']))
                  ->orWhereHas('room.floor.block.hostel', fn($hq) => $hq->where('gender_type', 'male'));
            })->count(),
            'female' => (clone $statsQuery)->where(function ($q) {
                $q->whereHas('student', fn($sq) => $sq->whereRaw('LOWER(gender) = ?', ['female']))
                  ->orWhereHas('room.floor.block.hostel', fn($hq) => $hq->where('gender_type', 'female'));
            })->count(),
        ];

        $canManageBookings = $user->can('manage_hostel_bookings') || $user->can('manage_hostels') || $user->hasRole('admin');

        return Inertia::render('Admin/Hostels/Bookings', [
            'bookings' => $bookings,
            'sessions' => $sessions,
            'currentSession' => $currentSession,
            'hostels' => $hostels,
            'stats' => [
                'total_bookings' => $totalBookingsCount,
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'cancelled' => $cancelledCount,
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'vacant_rooms' => $vacantRooms,
                'room_occupancy_rate' => $roomOccupancyRate,
                'total_capacity' => $totalCapacity,
                'available_rooms' => $availableBeds,
                'available_beds' => $availableBeds,
                'occupancy_rate' => $occupancyRate,
                'total_paid' => $totalPaid,
                'total_balance' => $totalBalance,
                'total_invoiced' => $totalInvoiceAmount,
                'gender_breakdown' => $genderBreakdown,
            ],
            'filters' => [
                'session_id' => $sessionId,
                'level' => $level,
                'hostel_id' => $hostelId,
                'block_id' => $blockId,
                'floor_id' => $floorId,
                'room_id' => $roomId,
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

    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('manage_hostel_bookings') &&
            !$user->can('manage_hostels') &&
            !$user->can('view_hostel_bookings') &&
            !$user->can('view_male_hostel_bookings') &&
            !$user->can('view_female_hostel_bookings') &&
            !$user->hasRole('admin')) {
            abort(403, 'Unauthorized access.');
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

        $blockId = $request->input('block_id');
        if ($blockId === 'all') $blockId = null;

        $floorId = $request->input('floor_id');
        if ($floorId === 'all') $floorId = null;

        $roomId = $request->input('room_id');
        if ($roomId === 'all') $roomId = null;

        $status = $request->input('status');
        if ($status === 'all') $status = null;

        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $gender = $request->input('gender', 'all');

        $isMaleSupervisor = ($user->can('view_male_hostel_bookings') || $user->hasRole('male_hostel_supervisor')) &&
                            !($user->can('view_female_hostel_bookings') || $user->hasRole('female_hostel_supervisor'));
        $isFemaleSupervisor = ($user->can('view_female_hostel_bookings') || $user->hasRole('female_hostel_supervisor')) &&
                              !($user->can('view_male_hostel_bookings') || $user->hasRole('male_hostel_supervisor'));
        $isGeneralAdmin = $user->can('manage_hostel_bookings') || $user->can('manage_hostels') || $user->hasRole('admin');

        if ($isMaleSupervisor && !$isGeneralAdmin) {
            $gender = 'male';
        } elseif ($isFemaleSupervisor && !$isGeneralAdmin) {
            $gender = 'female';
        }

        $query = HostelBooking::with([
            'student.user',
            'student.department',
            'room.floor.block.hostel',
            'session',
            'invoice.payments',
        ]);

        if ($sessionId) $query->where('session_id', $sessionId);
        if ($level) $query->whereHas('student', fn($q) => $q->where('current_level', $level));
        if ($hostelId) $query->whereHas('room.floor.block', fn($q) => $q->where('hostel_id', $hostelId));
        if ($blockId) $query->whereHas('room.floor', fn($q) => $q->where('hostel_block_id', $blockId));
        if ($floorId) $query->whereHas('room', fn($q) => $q->where('hostel_floor_id', $floorId));
        if ($roomId) $query->where('hostel_room_id', $roomId);

        if ($status === 'expired') {
            if ($currentSession) {
                $query->where('session_id', '!=', $currentSession->id)->whereIn('status', ['pending', 'confirmed']);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($status) {
            $query->where('status', $status);
        }

        if ($date) $query->whereDate('hostel_bookings.created_at', $date);
        if ($startDate) $query->whereDate('hostel_bookings.created_at', '>=', Carbon::parse($startDate)->startOfDay());
        if ($endDate) $query->whereDate('hostel_bookings.created_at', '<=', Carbon::parse($endDate)->endOfDay());

        if ($gender === 'male' || $gender === 'female') {
            $query->whereHas('room.floor.block.hostel', fn($q) => $q->where('gender_type', $gender));
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $filename = 'hostel_bookings_export_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'Student Name',
                'Matric Number',
                'Department',
                'Level',
                'Gender',
                'Academic Session',
                'Hostel',
                'Block',
                'Floor',
                'Room Number',
                'Booking Status',
                'Invoice Reference',
                'Invoice Amount',
                'Paid Amount',
                'Balance',
                'Invoice Status',
                'Date Allocated'
            ]);

            foreach ($bookings as $booking) {
                $invoice = $booking->invoice;
                $paid = 0;
                if ($invoice) {
                    if ($invoice->paid_amount > 0) {
                        $paid = (float) $invoice->paid_amount;
                    } elseif ($invoice->payments) {
                        $paid = (float) $invoice->payments
                            ->whereIn('status', ['successful', 'success', 'paid'])
                            ->sum('amount');
                    }
                }
                $invAmount = $invoice ? (float) $invoice->amount : 0;
                $balance = max(0, $invAmount - $paid);

                fputcsv($handle, [
                    $booking->student?->user?->name ?? 'N/A',
                    $booking->student?->matriculation_number ?? $booking->student?->matric_no ?? 'N/A',
                    $booking->student?->department?->name ?? 'N/A',
                    $booking->student?->current_level ?? 'N/A',
                    ucfirst($booking->student?->gender ?? 'N/A'),
                    $booking->session?->name ?? 'N/A',
                    $booking->room?->floor?->block?->hostel?->name ?? 'N/A',
                    $booking->room?->floor?->block?->name ?? 'N/A',
                    $booking->room?->floor?->name ?? 'N/A',
                    $booking->room?->room_number ?? 'N/A',
                    ucfirst($booking->status),
                    $invoice?->reference ?? 'N/A',
                    number_format($invAmount, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format($balance, 2, '.', ''),
                    ucfirst($invoice?->status ?? 'Unpaid'),
                    $booking->created_at ? $booking->created_at->format('Y-m-d H:i') : 'N/A'
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
