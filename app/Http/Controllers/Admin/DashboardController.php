<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Invoice;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Session Context
        $currentSession = Session::current();
        $sessionId = $request->input('session_id', $currentSession?->id);
        $selectedSession = Session::find($sessionId) ?? $currentSession;

        if (!$selectedSession) {
            // Fallback if absolutely no session exists
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'total_students' => 0,
                    'fresh_students' => 0,
                    'applications' => 0,
                    'revenue' => 0,
                    'active_courses' => 0,
                    'revenue_growth' => 0,
                    'student_growth' => 0,
                ],
                'recentActivity' => [],
                'sessions' => [],
                'filters' => ['session_id' => null],
                'currentSessionName' => 'N/A',
                'charts' => [
                    'revenue' => ['labels' => [], 'data' => []],
                    'faculty' => ['labels' => [], 'data' => []],
                ]
            ]);
        }

        // 2. Core Metrics
        // Total Students
        $totalStudents = Student::count();
        // Fresh Students (Admitted in this session)
        $freshStudents = Student::where('admitted_session_id', $sessionId)->count();

        // Revenue (Paid Invoices in session)
        $revenue = Invoice::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->sum('amount');

        // Active Courses (Courses with at least one registration)
        $activeCoursesCount = CourseRegistration::where('session_id', $sessionId)
            ->distinct('course_id')
            ->count('course_id');

        // Application Count (Using a simplistic check if Application model exists, else 0 or approximation)
        // Assuming Applicant role for now if no specific model usage in this context, or verify file existence.
        // We see 'App\Http\Controllers\Applicant\ApplicationController', so maybe `Application` model?
        // Let's stick to safe User role check for 'applicant' created_at in session timeframe? 
        // Or simplified: Just don't break if model missing. 
        // Let's use User role 'applicant' count as a proxy for "Applications" in pipeline.
        // Let's use User role 'applicant' count as a proxy for "Applications" in pipeline.
        $applicationsCount = User::whereHas('roles', function ($query) {
            $query->where('name', 'applicant');
        })->count();

        // 3. Trends (vs Previous Session if possible)
        // Find previous session
        $previousSession = Session::where('start_date', '<', $selectedSession->start_date)
            ->orderBy('start_date', 'desc')
            ->first();

        $revenueGrowth = 0;
        $studentGrowth = 0;

        if ($previousSession) {
            $prevRevenue = Invoice::where('session_id', $previousSession->id)->where('status', 'paid')->sum('amount');
            if ($prevRevenue > 0) {
                $revenueGrowth = (($revenue - $prevRevenue) / $prevRevenue) * 100;
            }

            $prevFresh = Student::where('admitted_session_id', $previousSession->id)->count();
            if ($prevFresh > 0) {
                $studentGrowth = (($freshStudents - $prevFresh) / $prevFresh) * 100;
            }
        }

        // 4. Recent Activity (Aggregated)
        // - New Payments
        $payments = Invoice::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->with(['user'])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'type' => 'payment',
                'title' => 'Payment Received',
                'description' => "{$inv->user->name} paid " . number_format($inv->amount),
                'amount' => $inv->amount,
                'time_ago' => $inv->updated_at->diffForHumans(),
                'timestamp' => $inv->updated_at,
                'icon' => 'CreditCard'
            ]);

        // - New Registrations
        $registrations = Student::where('admitted_session_id', $sessionId)
            ->with('user')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn($std) => [
                'id' => $std->id,
                'type' => 'student',
                'title' => 'New Student',
                'description' => "{$std->user->name} joined " . ($std->department ?? 'General'),
                'time_ago' => $std->created_at->diffForHumans(),
                'timestamp' => $std->created_at,
                'icon' => 'UserPlus'
            ]);

        // Merge and Sort
        $recentActivity = $payments->concat($registrations)
            ->sortByDesc('timestamp')
            ->take(6)
            ->values();


        // 5. Chart Data
        // 5. Chart Data
        // Revenue Trend (Monthly)
        $revenueTrend = Invoice::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $revenueChart = [
            'labels' => $revenueTrend->map(fn($r) => \Carbon\Carbon::createFromFormat('Y-m', $r->month)->format('M'))->toArray(),
            'data' => $revenueTrend->pluck('total')->toArray(),
        ];

        // Faculty Distribution (Pie Chart) - Renamed for clarity in UI to "Student Distribution by Faculty"
        $facultyStats = Student::select('faculties.name', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->leftJoin('departments', 'students.department_id', '=', 'departments.id')
            ->leftJoin('faculties', 'departments.faculty_id', '=', 'faculties.id')
            ->whereNotNull('faculties.name')
            ->groupBy('faculties.name')
            ->limit(5)
            ->get();

        $facultyChart = [
            'labels' => $facultyStats->pluck('name')->toArray(),
            'data' => $facultyStats->pluck('total')->toArray(),
        ];

        // Students by Level (Bar Chart)
        $levelStats = Student::select('current_level', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereNotNull('current_level')
            ->groupBy('current_level')
            ->orderBy('current_level')
            ->get();

        $levelChart = [
            'labels' => $levelStats->pluck('current_level')->map(fn($l) => $l . ' Lvl')->toArray(),
            'data' => $levelStats->pluck('total')->toArray(),
        ];

        // Top Programs (Doughnut)
        $programStats = Student::select('programmes.name', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->leftJoin('programmes', 'students.program_id', '=', 'programmes.id')
            ->whereNotNull('programmes.name')
            ->groupBy('programmes.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $programChart = [
            'labels' => $programStats->pluck('name')->map(fn($n) => \Illuminate\Support\Str::limit($n, 15))->toArray(),
            'data' => $programStats->pluck('total')->toArray(),
        ];

        // Staff by Department (Bar Chart)
        $staffDeptStats = \App\Models\Staff::select('departments.name', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->leftJoin('departments', 'staff.department_id', '=', 'departments.id')
            ->whereNotNull('departments.name')
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $staffDeptChart = [
            'labels' => $staffDeptStats->pluck('name')->toArray(),
            'data' => $staffDeptStats->pluck('total')->toArray(),
        ];


        // 6. Access Control Filtering
        $user = $request->user();
        $canViewFinance = $user->hasAnyPermission(['manage_payments', 'verify_payments', 'view_payments']) || $user->hasRole('admin');
        $canViewAdmissions = $user->hasAnyPermission(['admit_students', 'review_applications', 'view_applications']) || $user->hasRole('admin');
        $canViewResults = $user->hasAnyPermission(['manage_results', 'approve_results', 'view_results', 'manage_courses']) || $user->hasRole('admin');

        // Filter Recent Activity
        $recentActivity = $recentActivity->filter(function ($item) use ($canViewFinance, $canViewAdmissions, $canViewResults) {
            if ($item['type'] === 'payment')
                return $canViewFinance;
            if ($item['type'] === 'student')
                return $canViewAdmissions || $canViewResults;
            return true;
        })->values()->all();

        // Calculate Additional Metrics
        $outstandingFees = Invoice::where('session_id', $sessionId)
            ->where('status', '!=', 'paid')
            ->selectRaw('SUM(amount - paid_amount) as total')
            ->value('total');

        $outstandingFees = $outstandingFees ?? 0;

        $registeredStudentCount = CourseRegistration::where('session_id', $sessionId)
            ->distinct('student_id')
            ->count('student_id');

        $registrationCompliance = $totalStudents > 0 ? round(($registeredStudentCount / $totalStudents) * 100, 1) : 0;

        $genderStats = Student::select('gender', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        $malePercentage = $totalStudents > 0 ? round((($genderStats['male'] ?? 0) / $totalStudents) * 100, 1) : 0;
        $femalePercentage = $totalStudents > 0 ? round((($genderStats['female'] ?? 0) / $totalStudents) * 100, 1) : 0;

        // Structural Counts
        $structuralStats = [
            'faculties' => \App\Models\Faculty::count(),
            'departments' => \App\Models\Department::count(),
            'programs' => \App\Models\Programme::count(),
            'sessions' => Session::count(),
            'staff' => \App\Models\Staff::count(),
            'academic_staff' => \App\Models\Staff::where('is_academic', true)->count(),
            'non_academic_staff' => \App\Models\Staff::where('is_academic', false)->count(),
        ];

        // Stats Object with sensitivity filtering
        $dashboardStats = [
            'total_students' => $canViewResults ? $totalStudents : null,
            'fresh_students' => $canViewResults ? $freshStudents : null,
            'applications' => $canViewAdmissions ? $applicationsCount : null,
            'revenue' => $canViewFinance ? $revenue : null,
            'active_courses' => $canViewResults ? $activeCoursesCount : null,
            'revenue_growth' => $canViewFinance ? round($revenueGrowth, 1) : null,
            'student_growth' => $canViewResults ? round($studentGrowth, 1) : null,
            'outstanding_fees' => $canViewFinance ? $outstandingFees : null,
            'registration_compliance' => $canViewResults ? $registrationCompliance : null,
            'gender_distribution' => $canViewResults ? ['male' => $malePercentage, 'female' => $femalePercentage] : null,
            'structural' => $structuralStats,
        ];

        // 7. View Data
        $sessions = Session::orderBy('start_date', 'desc')->get(['id', 'name']);

        // 7. My Course Allocations & Timetable (If Staff)
        $myAllocations = [];
        $myTimetable = [];
        if ($user->hasAnyRole(['lecturer', 'course_coordinator', 'dean', 'hod'])) {
            $myAllocations = \App\Models\CourseAllocation::whereHas('staff', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->where('session_id', $sessionId)
                ->with(['course'])
                ->get();

            // Get course IDs from allocations
            $courseIds = $myAllocations->pluck('course_id');

            // Fetch Timetable
            $myTimetable = \App\Models\Timetable::whereIn('course_id', $courseIds)
                ->where('session_id', $sessionId)
                ->with(['course'])
                ->get();

            // Lecturer Stats
            $lecturerStats = [
                'total_students' => \App\Models\CourseRegistration::whereIn('course_id', $courseIds)
                    ->where('session_id', $sessionId)
                    ->distinct('student_id')
                    ->count('student_id'),
                'total_courses' => $myAllocations->count(),
                'classes_today' => $myTimetable->filter(function ($t) {
                    return strtolower($t->day) === strtolower(now()->format('l'));
                })->count(),
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'currentSessionName' => $selectedSession->name,
            'filters' => ['session_id' => $sessionId],
            'sessions' => $sessions,
            'stats' => $dashboardStats,
            'lecturerStats' => $lecturerStats ?? null,
            'recentActivity' => $recentActivity,
            'charts' => [
                'revenue' => $canViewFinance ? $revenueChart : ['labels' => [], 'data' => []],
                'faculty' => $canViewResults ? $facultyChart : ['labels' => [], 'data' => []],
                'level' => $canViewResults ? $levelChart : ['labels' => [], 'data' => []],
                'program' => $canViewResults ? $programChart : ['labels' => [], 'data' => []],
                'staff_department' => $canViewResults ? $staffDeptChart : ['labels' => [], 'data' => []], // Using canViewResults as proxy for general admin view for now, or use canViewAdmissions? Or new perm? Let's assume broad access or same as other structural charts which seem to be tied to results/courses logic in current code structure.
            ],
            'myAllocations' => $myAllocations,
            'myTimetable' => $myTimetable,
        ]);
    }
}
