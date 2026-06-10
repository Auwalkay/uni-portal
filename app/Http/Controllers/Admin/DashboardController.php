<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payroll;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Session Context
        $currentSession = Session::current();
        $sessionId = $request->input('session_id', $currentSession?->id);
        $selectedSession = Session::find($sessionId) ?? $currentSession;

        if (! $selectedSession) {
            // Fallback if absolutely no session exists
            $user = $request->user();
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'total_students' => 0,
                    'fresh_students' => 0,
                    'applications' => 0,
                    'revenue' => 0,
                    'active_courses' => 0,
                    'revenue_growth' => 0,
                    'student_growth' => 0,
                    'outstanding_fees' => 0,
                    'registration_compliance' => 0,
                    'gender_distribution' => ['male' => 0, 'female' => 0],
                    'total_outflow' => 0,
                    'net_cash_flow' => 0,
                    'active_students' => 0,
                ],
                'recentActivity' => [],
                'sessions' => [],
                'filters' => ['session_id' => null],
                'currentSessionName' => 'N/A',
                'charts' => [
                    'revenue' => ['labels' => [], 'data' => []],
                    'faculty' => ['labels' => [], 'data' => []],
                    'financial_trend' => ['labels' => [], 'inflow' => [], 'outflow' => []],
                    'expense_categories' => ['labels' => [], 'data' => []],
                    'level' => ['labels' => [], 'data' => []],
                    'program' => ['labels' => [], 'data' => []],
                    'staff_department' => ['labels' => [], 'data' => []],
                ],
                'userRole' => $user->hasRole('admin') ? 'admin' : 'staff',
                'can' => [
                    'manage_students' => $user->can('manage_staff'),
                    'manage_finance' => $user->can('view_revenue_stats'),
                    'manage_results' => $user->can('manage_results'),
                    'manage_settings' => $user->can('manage_system_settings'),
                    'view_global_analytics' => $user->can('view_global_analytics'),
                    'view_system_status' => $user->can('view_system_status'),
                ],
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
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'type' => 'payment',
                'title' => 'Payment Received',
                'description' => "{$inv->user->name} paid ".number_format($inv->amount),
                'amount' => $inv->amount,
                'time_ago' => $inv->updated_at->diffForHumans(),
                'timestamp' => $inv->updated_at,
                'icon' => 'CreditCard',
            ]);

        // - New Registrations
        $registrations = Student::where('admitted_session_id', $sessionId)
            ->with(['user', 'department'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn ($std) => [
                'id' => $std->id,
                'type' => 'student',
                'title' => 'New Student',
                'description' => "{$std->user->name} joined " . ($std->department->name ?? 'General'),
                'time_ago' => $std->created_at->diffForHumans(),
                'timestamp' => $std->created_at,
                'icon' => 'UserPlus',
                'department_id' => $std->department_id,
            ]);

        // - Recent Results
        $results = CourseRegistration::where('session_id', $sessionId)
            ->whereNotNull('score')
            ->with(['student.user', 'course'])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn ($reg) => [
                'id' => $reg->id,
                'type' => 'result',
                'title' => 'Result Entered',
                'description' => "Grade for {$reg->student->user->name} in {$reg->course->code}",
                'time_ago' => $reg->updated_at->diffForHumans(),
                'timestamp' => $reg->updated_at,
                'icon' => 'FileText',
                'course_id' => $reg->course_id,
            ]);

        // Merge and Sort
        $recentActivity = $payments->concat($registrations)->concat($results)
            ->sortByDesc('timestamp')
            ->take(8)
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
            'labels' => $revenueTrend->map(fn ($r) => \Carbon\Carbon::createFromFormat('Y-m', (string) $r->month)->format('M'))->toArray(),
            'data' => $revenueTrend->pluck('total')->toArray(),
        ];

        // Faculty Distribution (Pie Chart) - Renamed for clarity in UI to "Student Distribution by Faculty"
        $facultyStats = Student::select('faculties.name', DB::raw('count(*) as total'))
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
        $levelStats = Student::select('current_level', DB::raw('count(*) as total'))
            ->whereNotNull('current_level')
            ->groupBy('current_level')
            ->orderBy('current_level')
            ->get();

        $levelChart = [
            'labels' => $levelStats->pluck('current_level')->map(fn ($l) => $l.' Lvl')->toArray(),
            'data' => $levelStats->pluck('total')->toArray(),
        ];

        // Top Programs (Doughnut)
        $programStats = Student::select('programmes.name', DB::raw('count(*) as total'))
            ->leftJoin('programmes', 'students.program_id', '=', 'programmes.id')
            ->whereNotNull('programmes.name')
            ->groupBy('programmes.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $programChart = [
            'labels' => $programStats->pluck('name')->map(fn ($n) => \Illuminate\Support\Str::limit((string) $n, 15))->toArray(),
            'data' => $programStats->pluck('total')->toArray(),
        ];

        // Expense Trend (Monthly)
        $expenseTrend = Expense::where('status', 'approved')
            ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Combined Financial Trend
        $financialTrendLabels = $revenueTrend->pluck('month')->merge($expenseTrend->pluck('month'))->unique()->sort()->values();

        $combinedFinancialChart = [
            'labels' => $financialTrendLabels->map(fn ($m) => \Carbon\Carbon::createFromFormat('Y-m', (string) $m)->format('M'))->toArray(),
            'inflow' => $financialTrendLabels->map(fn ($m) => $revenueTrend->firstWhere('month', $m)?->total ?? 0)->toArray(),
            'outflow' => $financialTrendLabels->map(fn ($m) => $expenseTrend->get((string) $m)?->total ?? 0)->toArray(),
        ];

        // Expense by Category (Doughnut)
        $expenseByCategory = Expense::where('status', 'approved')
            ->with('category')
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->get();

        $expenseCategoryChart = [
            'labels' => $expenseByCategory->map(fn ($e) => $e->category?->name ?? 'Uncategorized')->toArray(),
            'data' => $expenseByCategory->pluck('total')->toArray(),
        ];

        // Staff by Department (Bar Chart)
        $staffDeptStats = \App\Models\Staff::select('departments.name', DB::raw('count(*) as total'))
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
        $canViewFinance = $user->can('view_revenue_stats');
        $canViewAdmissions = $user->can('view_admission_stats');
        $canViewResults = $user->can('view_academic_stats');
        $canViewStaff = $user->can('manage_staff');
        $canViewSettings = $user->can('manage_system_settings');
        $canViewGlobalAnalytics = $user->can('view_global_analytics');
        $canViewActivity = $user->can('view_recent_activities');
        $canViewSystemStatus = $user->can('view_system_status');

        // Filter Recent Activity
        $recentActivity = $canViewActivity ? $recentActivity->filter(function ($item) use ($user, $canViewFinance, $canViewAdmissions, $canViewResults) {
            // Finance Filter
            if ($item['type'] === 'payment') {
                return $canViewFinance;
            }

            // Student Filter (Restrict by department for non-admins)
            if ($item['type'] === 'student') {
                if (!$canViewAdmissions && !$canViewResults) return false;
                
                if (!$user->can('manage_users')) { // Not a super admin/registrar
                    $staff = $user->staff;
                    if ($staff && $staff->department_id && isset($item['department_id'])) {
                        return $staff->department_id === $item['department_id'];
                    }
                }
                return true;
            }

            // Result Filter (Restrict by allocation for non-admins)
            if ($item['type'] === 'result') {
                if (!$canViewResults) return false;

                if (!$user->can('manage_results')) {
                    return \App\Models\CourseAllocation::where('course_id', $item['course_id'])
                        ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
                        ->exists();
                }
                return true;
            }

            return true;
        })->values()->all() : [];

        // High Intensity Admissions Stats
        $admissionsFunnel = [
            'total_applicants' => $applicationsCount,
            'screened_applicants' => User::role('applicant')->whereHas('student', function ($q) {
                $q->whereNotNull('matriculation_number');
            })->count(), // Proxy for "admitted"
            'pending_screening' => User::role('applicant')->whereDoesntHave('student')->count(),
        ];

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

        $genderStats = Student::select('gender', DB::raw('count(*) as count'))
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
            'total_students' => $canViewGlobalAnalytics ? $totalStudents : null,
            'fresh_students' => $canViewGlobalAnalytics ? $freshStudents : null,
            'applications' => $canViewAdmissions ? $applicationsCount : null,
            'revenue' => $canViewFinance ? $revenue : null,
            'active_courses' => $canViewResults ? $activeCoursesCount : null,
            'revenue_growth' => $canViewFinance ? round($revenueGrowth, 1) : null,
            'student_growth' => $canViewGlobalAnalytics ? round($studentGrowth, 1) : null,
            'outstanding_fees' => $canViewFinance ? $outstandingFees : null,
            'registration_compliance' => $canViewGlobalAnalytics ? $registrationCompliance : null,
            'gender_distribution' => $canViewGlobalAnalytics ? ['male' => $malePercentage, 'female' => $femalePercentage] : null,
            'structural' => $canViewGlobalAnalytics ? $structuralStats : null,
            'total_outflow' => $canViewFinance ? (float) Expense::where('status', 'approved')->sum('amount') + (float) Payroll::where('status', 'paid')->sum('total_amount') : null,
            'net_cash_flow' => $canViewFinance ? ((float) Invoice::where('status', 'paid')->sum('amount')) - ((float) Expense::where('status', 'approved')->sum('amount') + (float) Payroll::where('status', 'paid')->sum('total_amount')) : null,
            'admissions_funnel' => $canViewAdmissions ? $admissionsFunnel : null,
            'active_students' => $canViewGlobalAnalytics ? Student::count() : null,
        ];

        // 7. Determine Primary Role for UI Layout
        $primaryRole = 'admin'; // Default
        if ($user->hasRole('admin')) {
            $primaryRole = 'admin';
        } elseif ($user->hasAnyRole(['bursar', 'finance_officer', 'finance_clerk'])) {
            $primaryRole = 'finance';
        } elseif ($user->hasAnyRole(['lecturer', 'course_coordinator', 'dean', 'hod'])) {
            $primaryRole = 'academic';
        } elseif ($user->hasAnyRole(['registrar', 'admissions_manager', 'admissions_officer', 'admissions_clerk'])) {
            $primaryRole = 'admissions';
        }

        // 8. View Data
        $sessions = Session::orderBy('start_date', 'desc')->get(['id', 'name']);

        // 7. My Course Allocations & Timetable (If Staff)
        $myAllocations = [];
        $myTimetable = [];
        $courseIds = [];
        if ($user->hasAnyRole(['lecturer', 'course_coordinator', 'dean', 'hod'])) {
            $myAllocations = \App\Models\CourseAllocation::whereHas('staff', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->where('session_id', $sessionId)
                ->with(['course'])
                ->get();

            $courseIds = $myAllocations->pluck('course_id')->toArray();

            // Fetch Timetable from Cache
            if ($user->staff) {
                $myTimetable = \App\Services\AcademicCacheService::getStaffTimetable($user->staff->id, $sessionId);
            }

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
        
        // 9. Overwrite stats for Lecturers/Allocated Staff (Strictly their own data)
        if (!$canViewGlobalAnalytics && isset($lecturerStats)) {
            $dashboardStats['total_students'] = $lecturerStats['total_students'];
            $dashboardStats['active_courses'] = $lecturerStats['total_courses'];
            $dashboardStats['active_students'] = $lecturerStats['total_students'];
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
                'financial_trend' => $canViewFinance ? $combinedFinancialChart : ['labels' => [], 'data' => [], 'inflow' => [], 'outflow' => []],
                'expense_categories' => $canViewFinance ? $expenseCategoryChart : ['labels' => [], 'data' => []],
                'faculty' => $canViewGlobalAnalytics ? $facultyChart : ['labels' => [], 'data' => []],
                'level' => $canViewGlobalAnalytics ? $levelChart : ['labels' => [], 'data' => []],
                'program' => $canViewGlobalAnalytics ? $programChart : ['labels' => [], 'data' => []],
                'staff_department' => $canViewGlobalAnalytics ? $staffDeptChart : ['labels' => [], 'data' => []],
            ],
            'myTimetable' => $myTimetable,
            'userRole' => $primaryRole,
            'can' => [
                'manage_students' => $user->can('manage_staff'), // In this system registrar/admin manages students
                'manage_finance' => $canViewFinance,
                'manage_results' => $user->can('manage_results'),
                'manage_settings' => $user->can('manage_system_settings'),
                'view_global_analytics' => $canViewGlobalAnalytics,
                'view_system_status' => $canViewSystemStatus,
            ],
        ]);
    }
}
