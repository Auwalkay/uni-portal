<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Hostel;
use App\Models\HostelBooking;
use App\Models\HostelFee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Session;
use App\Models\Student;
use App\Models\HostelRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class AccommodationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $currentSession = Session::current();

        if (!$currentSession) {
            return redirect()->route('student.dashboard')->with('error', 'No active academic session found.');
        }

        // 1. School Fee Check
        $hasPaidFees = Invoice::where('user_id', $user->id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        // 2. Course Registration Check (Optional for hostel booking)
        $hasRegisteredCourses = true; // Set to true as it is no longer a blocker

        // Check for existing booking
        $existingBooking = HostelBooking::with(['room.floor.block.hostel', 'invoice'])
            ->where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->first();

        // If they haven't met requirements, just pass the statuses to the view so it can show the red locks
        if (!$hasPaidFees) {
            return Inertia::render('Student/Accommodation/Index', [
                'hasPaidFees' => $hasPaidFees,
                'hasRegisteredCourses' => $hasRegisteredCourses,
                'hostels' => [],
                'existingBooking' => $existingBooking,
            ]);
        }

        // Get Available Hostels based on gender
        $studentGender = strtolower($student->gender ?? '');

        $hostels = Hostel::with(['blocks.floors.rooms.bookings'])
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
            'hostels' => $hostels,
            'existingBooking' => $existingBooking,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'hostel_room_id' => 'required|exists:hostel_rooms,id',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $currentSession = Session::current();

        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // Validate basic rules again just to be safe
        $hasPaidFees = Invoice::where('user_id', $user->id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();


        if (!$hasPaidFees) {
            return back()->with('error', 'You must pay school fees before booking.');
        }

        // Check for existing booking
        $existingBooking = HostelBooking::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->first();

        if ($existingBooking) {
            return back()->with('error', 'You already have an accommodation booking for this session.');
        }

        $room = HostelRoom::with('floor.block.hostel')->findOrFail($request->hostel_room_id);

        // Check capacity
        $bookedCount = $room->bookings()->whereIn('status', ['pending', 'confirmed'])->count();
        if ($bookedCount >= $room->capacity) {
            return back()->with('error', 'This room is already fully booked.');
        }

        DB::beginTransaction();
        try {
            // Find Hostel Fee. Fall back to global if no specific fee for this hostel
            $hostelId = $room->floor->block->hostel->id;
            $fee = HostelFee::where('session_id', $currentSession->id)
                ->where(function ($q) use ($hostelId) {
                    $q->where('hostel_id', $hostelId)->orWhereNull('hostel_id');
                })
                ->orderBy('hostel_id', 'desc') // specific hostel fee first (null comes last)
                ->first();

            if (!$fee) {
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

            // Generate Invoice
            $reference = 'HST-' . strtoupper(uniqid());

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'session_id' => $currentSession->id,
                'reference' => $reference,
                'type' => 'hostel_fee',
                'amount' => $finalAmount,
                'status' => 'pending',
                'due_date' => now()->addDays(7),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Hostel Accommodation Fee (' . $room->floor->block->hostel->name . ' - Block: ' . $room->floor->block->name . ', Room: ' . $room->room_number . ')',
                'amount' => $fee->amount,
            ]);

            if ($discountAmount > 0) {
                $discountDesc = $student->scholarship->type === 'fixed'
                    ? 'Scholarship Discount (' . $student->scholarship->name . ' - Fixed ₦' . number_format($student->scholarship->amount, 2) . ')'
                    : 'Scholarship Discount (' . $student->scholarship->name . ' - ' . floatval($student->scholarship->percentage) . '%)';
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $discountDesc,
                    'amount' => -$discountAmount,
                ]);
            }

            // Create Booking
            HostelBooking::create([
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
                'hostel_room_id' => $room->id,
                'invoice_id' => $invoice->id,
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('student.payments.index')
                ->with('success', 'Room booked successfully! Please proceed to pay your Hostel Fee invoice to confirm your reservation.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process booking. Please try again: ' . $e->getMessage());
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

        if (!$booking) {
            return back()->with('error', 'No confirmed accommodation booking found for the current session.');
        }

        $pdf = Pdf::loadView('documents.accommodation_slip', [
            'student' => $student,
            'booking' => $booking,
            'session' => $currentSession,
        ]);

        return $pdf->download("Accommodation_Slip_slip.pdf");
    }

    public function downloadPaymentSlip()
    {
        $user = Auth::user();
        $currentSession = Session::current();

        $booking = HostelBooking::with([
            'invoice.payments' => function ($q) {
                $q->where('status', 'success');
            }
        ])
            ->where('student_id', function ($q) use ($user) {
                $q->select('id')->from('students')->where('user_id', $user->id);
            })
            ->where('session_id', $currentSession->id)
            ->first();

        if (!$booking || !$booking->invoice) {
            return back()->with('error', 'No booking or invoice found.');
        }

        $payment = $booking->invoice->payments->first();

        if (!$payment) {
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
