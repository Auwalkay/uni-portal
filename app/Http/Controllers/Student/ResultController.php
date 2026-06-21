<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ResultController extends Controller
{
    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function index()
    {
        $student = Student::where('user_id', Auth::id())->with('program')->firstOrFail();

        // System configuration settings
        $enforceSchoolFee = filter_var(\App\Models\SystemSetting::get('enforce_school_fee_for_results', false), FILTER_VALIDATE_BOOLEAN);
        $enforceHostelFee = filter_var(\App\Models\SystemSetting::get('enforce_hostel_fee_for_results', false), FILTER_VALIDATE_BOOLEAN);

        // Fetch all unique sessions the student has registered for
        $sessionIds = CourseRegistration::where('student_id', $student->id)
            ->distinct()
            ->pluck('session_id');

        $sessions = Session::whereIn('id', $sessionIds)
            ->orderBy('start_date', 'desc')
            ->get();

        $programme = $student->program;
        $overrides = collect();
        if ($programme) {
            $overrides = \Illuminate\Support\Facades\DB::table('course_programme')
                ->where('programme_id', $programme->id)
                ->pluck('is_compulsory', 'course_id');
        }

        $userId = Auth::id();
        $cacheKey = "student_results_index_{$userId}";

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($student, $userId, $enforceSchoolFee, $enforceHostelFee, $sessions, $programme, $overrides) {
            // Fix N+1 queries by pre-fetching all school fee invoices and hostel bookings for this student
            $schoolFeeInvoices = collect();
            if ($enforceSchoolFee) {
                $schoolFeeInvoices = \App\Models\Invoice::where('user_id', $userId)
                    ->where('type', 'school_fee')
                    ->get()
                    ->keyBy('session_id');
            }

            $hostelBookings = collect();
            if ($enforceHostelFee) {
                $hostelBookings = \App\Models\HostelBooking::where('student_id', $student->id)
                    ->with('invoice')
                    ->get()
                    ->keyBy('session_id');
            }

            $history = [];

            foreach ($sessions as $session) {
                // Check clearance for this session
                $schoolFeeCleared = true;
                if ($enforceSchoolFee) {
                    $schoolFeeInvoice = $schoolFeeInvoices->get($session->id);
                    $schoolFeeCleared = $schoolFeeInvoice && $schoolFeeInvoice->status === 'paid';
                }

                $hostelFeeCleared = true;
                if ($enforceHostelFee) {
                    $hostelBooking = $hostelBookings->get($session->id);
                    if ($hostelBooking) {
                        $hostelInvoice = $hostelBooking->invoice;
                        $hostelFeeCleared = $hostelInvoice && $hostelInvoice->status === 'paid';
                    }
                }

                $sessionData = [
                    'id' => $session->id,
                    'name' => $session->name,
                    'is_current' => $session->is_current,
                    'semesters' => []
                ];

                // Get semesters for this session
                $registrationsInSession = CourseRegistration::where('student_id', $student->id)
                    ->where('session_id', $session->id)
                    ->where('is_published', true)
                    ->with(['course', 'semester'])
                    ->get()
                    ->groupBy('semester.name');

                foreach ($registrationsInSession as $semesterName => $regs) {
                    $isSecondSem = stripos($semesterName, 'Second') !== false || strpos($semesterName, '2') !== false;
                    $isBlocked = $isSecondSem && (!$schoolFeeCleared || !$hostelFeeCleared);

                    foreach ($regs as $reg) {
                        if ($reg->course) {
                            $reg->course->is_compulsory = $overrides->has($reg->course->id) ? (bool)$overrides->get($reg->course->id) : false;
                        }
                        if ($isBlocked) {
                            $reg->score = null;
                            $reg->grade = 'Locked';
                            $reg->grade_point = null;
                        }
                    }

                    $gpa = $isBlocked ? 0 : $this->gradingService->calculateGPA($regs);

                    $sessionData['semesters'][] = [
                        'name' => $semesterName,
                        'gpa' => $gpa,
                        'is_blocked' => $isBlocked,
                        'school_fee_cleared' => $schoolFeeCleared,
                        'hostel_fee_cleared' => $hostelFeeCleared,
                        'courses' => $regs
                    ];
                }

                usort($sessionData['semesters'], function ($a, $b) {
                    return strpos($a['name'], 'Second') !== false ? 1 : -1;
                });

                $history[] = $sessionData;
            }

            // CGPA calculation: only include allowed semesters
            $allPublishedRegs = CourseRegistration::where('student_id', $student->id)
                ->where('is_published', true)
                ->with(['course', 'semester'])
                ->get();

            $cgpaRegs = $allPublishedRegs->filter(function ($reg) use ($enforceSchoolFee, $enforceHostelFee, $schoolFeeInvoices, $hostelBookings) {
                $semesterName = $reg->semester?->name ?? '';
                $isSecondSem = stripos($semesterName, 'Second') !== false || strpos($semesterName, '2') !== false;
                
                if (!$isSecondSem) {
                    return true; // First Sem is never blocked
                }

                // Check clearance in the registration's session
                $schoolFeeCleared = true;
                if ($enforceSchoolFee) {
                    $schoolFeeInvoice = $schoolFeeInvoices->get($reg->session_id);
                    $schoolFeeCleared = $schoolFeeInvoice && $schoolFeeInvoice->status === 'paid';
                }

                $hostelFeeCleared = true;
                if ($enforceHostelFee) {
                    $hostelBooking = $hostelBookings->get($reg->session_id);
                    if ($hostelBooking) {
                        $hostelInvoice = $hostelBooking->invoice;
                        $hostelFeeCleared = $hostelInvoice && $hostelInvoice->status === 'paid';
                    }
                }

                return $schoolFeeCleared && $hostelFeeCleared;
            });

            $cgpa = $cgpaRegs->isEmpty() ? 0 : $this->gradingService->calculateGPA($cgpaRegs);

            return [
                'history' => $history,
                'cgpa' => $cgpa
            ];
        });

        return Inertia::render('Student/Results/Index', $data);
    }
}
