<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelBooking;
use App\Models\HostelFee;
use App\Models\HostelRoom;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Session;
use App\Models\Student;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccommodationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $currentSession = Session::current();

        if (! $currentSession) {
            return redirect()->route('student.dashboard')->with('error', 'No active academic session found.');
        }

        $isBookingActive = filter_var(SystemSetting::get('enable_hostel_booking', true), FILTER_VALIDATE_BOOLEAN);

        // 1. School Fee Check
        $hasPaidFees = Invoice::where('user_id', $user->id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        // 2. Course Registration Check (Optional for hostel booking)
        $hasRegisteredCourses = true; // Set to true as it is no longer a blocker

        // Check for existing active booking
        $existingBooking = HostelBooking::with(['room.floor.block.hostel', 'invoice'])
            ->where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        // If they haven't met requirements or booking is disabled, pass correct statuses to the view
        if (! $hasPaidFees || ! $isBookingActive) {
            return Inertia::render('Student/Accommodation/Index', [
                'hasPaidFees' => $hasPaidFees,
                'hasRegisteredCourses' => $hasRegisteredCourses,
                'isBookingActive' => $isBookingActive,
                'hostels' => [],
                'existingBooking' => $existingBooking,
            ]);
        }

        // Get Available Hostels based on gender
        $studentGender = strtolower($student->gender ?? '');

        $hostels = Hostel::where('is_visible', true)
            ->with(['blocks.floors.rooms' => function ($q) use ($currentSession) {
                $q->where('is_visible', true)->with(['bookings' => function ($bq) use ($currentSession) {
                    $bq->where('session_id', $currentSession->id);
                }]);
            }])
            ->when($studentGender, function ($q) use ($studentGender) {
                $q->whereIn('gender_type', [$studentGender, 'mixed']);
            }, function ($q) {
                // If gender is missing, we don't return any specific-gender hostels
                $q->where('gender_type', 'mixed');
            })
            ->get();

        // Calculate availability for each room
        $hostels->each(function ($hostel) {
            $hostel->blocks->each(function ($block) {
                $block->floors->each(function ($floor) {
                    $floor->rooms->each(function ($room) {
                        $bookedCount = $room->bookings->whereIn('status', ['pending', 'confirmed'])->count();
                        $room->is_full = $bookedCount >= $room->capacity;
                        $room->available_beds = max(0, $room->capacity - $bookedCount);
                    });
                });
            });
        });

        return Inertia::render('Student/Accommodation/Index', [
            'hasPaidFees' => $hasPaidFees,
            'hasRegisteredCourses' => $hasRegisteredCourses,
            'isBookingActive' => $isBookingActive,
            'hostels' => $hostels,
            'existingBooking' => $existingBooking,
        ]);
    }

    public function store(Request $request)
    {
        $bookingEnabled = filter_var(SystemSetting::get('enable_hostel_booking', true), FILTER_VALIDATE_BOOLEAN);
        if (! $bookingEnabled) {
            return back()->with('error', 'Hostel bookings are currently closed by the administration.');
        }

        $request->validate([
            'hostel_room_id' => 'required|exists:hostel_rooms,id',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $currentSession = Session::current();

        if (! $currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // Validate basic rules again just to be safe
        $hasPaidFees = Invoice::where('user_id', $user->id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        if (! $hasPaidFees) {
            return back()->with('error', 'You must pay school fees before booking.');
        }

        // Check for existing active booking
        $existingBooking = HostelBooking::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingBooking) {
            return back()->with('error', 'You already have an active accommodation booking for this session.');
        }

        DB::beginTransaction();
        try {
            // Lock room row for update to prevent concurrent overbooking race conditions
            $room = HostelRoom::where('id', $request->hostel_room_id)
                ->with('floor.block.hostel')
                ->lockForUpdate()
                ->firstOrFail();

            if ($room->is_suspended) {
                DB::rollBack();
                return back()->with('error', 'This room is currently suspended and cannot be booked.');
            }

            if (! $room->is_visible) {
                DB::rollBack();
                return back()->with('error', 'This room is not currently open for bookings.');
            }

            if (! $room->floor->block->hostel->is_visible) {
                DB::rollBack();
                return back()->with('error', 'This hostel is not currently open for bookings.');
            }

            // Check capacity for current session while room is locked
            $bookedCount = $room->bookings()
                ->where('session_id', $currentSession->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($bookedCount >= $room->capacity) {
                DB::rollBack();
                return back()->with('error', 'This room was just reserved by another student. Please select another available unit.');
            }
            // Find Hostel Fee. Fall back to global if no specific fee for this hostel
            $hostelId = $room->floor->block->hostel->id;
            $fee = HostelFee::where('session_id', $currentSession->id)
                ->where(function ($q) use ($hostelId) {
                    $q->where('hostel_id', $hostelId)->orWhereNull('hostel_id');
                })
                ->orderBy('hostel_id', 'desc') // specific hostel fee first (null comes last)
                ->first();

            if (! $fee) {
                throw new \Exception('Accommodation fees have not been configured for this session.');
            }

            // Calculate Scholarship Discount for Hostel
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

            // Check if there is an existing hostel fee invoice for this session
            $invoice = Invoice::where('user_id', $user->id)
                ->where('session_id', $currentSession->id)
                ->where('type', 'hostel_fee')
                ->first();

            if ($invoice) {
                // If invoice already exists, check if it is paid or partially paid
                $isPaid = in_array($invoice->status, ['paid', 'partial']);
                $bookingStatus = $isPaid ? 'confirmed' : 'pending';
                
                // Clear old items and recreate with new room details
                $invoice->items()->delete();
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Hostel Accommodation Fee ('.$room->floor->block->hostel->name.' - Block: '.$room->floor->block->name.', Room: '.$room->room_number.')',
                    'amount' => $fee->amount,
                ]);

                if ($discountAmount > 0) {
                    $discountDesc = $student->scholarship->type === 'fixed'
                        ? 'Scholarship Discount ('.$student->scholarship->name.' - Fixed ₦'.number_format($student->scholarship->amount, 2).')'
                        : 'Scholarship Discount ('.$student->scholarship->name.' - '.floatval($student->scholarship->percentage).'%)';
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $discountDesc,
                        'amount' => -$discountAmount,
                    ]);
                }
                
                // If the new room has a different fee, we update the invoice amount if unpaid
                if (!$isPaid) {
                    $invoice->update(['amount' => $finalAmount]);
                }
            } else {
                // Generate Invoice
                $reference = 'HST-'.strtoupper(uniqid());

                $expiryDays = intval(SystemSetting::get('hostel_booking_expiry_days', 2));

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'session_id' => $currentSession->id,
                    'reference' => $reference,
                    'type' => 'hostel_fee',
                    'amount' => $finalAmount,
                    'status' => 'pending',
                    'due_date' => now()->addDays($expiryDays),
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Hostel Accommodation Fee ('.$room->floor->block->hostel->name.' - Block: '.$room->floor->block->name.', Room: '.$room->room_number.')',
                    'amount' => $fee->amount,
                ]);

                if ($discountAmount > 0) {
                    $discountDesc = $student->scholarship->type === 'fixed'
                        ? 'Scholarship Discount ('.$student->scholarship->name.' - Fixed ₦'.number_format($student->scholarship->amount, 2).')'
                        : 'Scholarship Discount ('.$student->scholarship->name.' - '.floatval($student->scholarship->percentage).'%)';
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $discountDesc,
                        'amount' => -$discountAmount,
                    ]);
                }
                
                $bookingStatus = 'pending';
            }

            // Create Booking
            HostelBooking::create([
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
                'hostel_room_id' => $room->id,
                'invoice_id' => $invoice->id,
                'status' => $bookingStatus,
            ]);

            DB::commit();

            return redirect()->route('student.payments.index')
                ->with('success', 'Room booked successfully! Please proceed to pay your Hostel Fee invoice to confirm your reservation.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to process booking. Please try again: '.$e->getMessage());
        }
    }

    public function downloadAccommodationSlip()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)
            ->with(['user', 'department', 'faculty'])
            ->firstOrFail();

        $currentSession = Session::current();

        $booking = HostelBooking::with(['room.floor.block.hostel', 'invoice'])
            ->where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->where('status', 'confirmed')
            ->first();

        if (! $booking || ! $booking->invoice || ! in_array($booking->invoice->status, ['paid', 'partial'])) {
            return back()->with('error', 'Accommodation slip can only be downloaded once the accommodation payment is confirmed.');
        }

        $pdf = Pdf::loadView('documents.accommodation_slip', [
            'student' => $student,
            'booking' => $booking,
            'session' => $currentSession,
        ]);

        return $pdf->download('Accommodation_Slip_slip.pdf');
    }

    public function downloadPaymentSlip()
    {
        $user = Auth::user();
        $currentSession = Session::current();

        $booking = HostelBooking::with([
            'invoice.payments' => function ($q) {
                $q->where('status', 'success');
            },
        ])
            ->where('student_id', function ($q) use ($user) {
                $q->select('id')->from('students')->where('user_id', $user->id);
            })
            ->where('session_id', $currentSession->id)
            ->first();

        if (! $booking || ! $booking->invoice) {
            return back()->with('error', 'No booking or invoice found.');
        }

        $payment = $booking->invoice->payments->first();

        if (! $payment) {
            return back()->with('error', 'No successful payment found for this booking.');
        }

        $pdf = Pdf::loadView('documents.receipt', [
            'user' => $user,
            'invoice' => $booking->invoice,
            'payment' => $payment,
        ]);

        return $pdf->download("Hostel_Payment_Receipt_{$booking->invoice->reference}.pdf");
    }
}
