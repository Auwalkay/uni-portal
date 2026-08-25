<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Session Context
        $currentSession = Session::current();
        $sessionId = $request->input('session_id', $currentSession?->id);
        $selectedSession = Session::find($sessionId) ?? $currentSession;

        // 2. Resolve User Role & Permissions First
        $canViewFinance = $user->can('view_revenue_stats');
        $canViewAdmissions = $user->can('view_admission_stats');
        $canViewResults = $user->can('view_academic_stats');
        $canViewStaff = $user->can('manage_staff');
        $canViewSettings = $user->can('manage_system_settings');
        $canViewGlobalAnalytics = $user->can('view_global_analytics');
        $canViewActivity = $user->can('view_recent_activities');
        $canViewSystemStatus = $user->can('view_system_status');

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

        if (! $selectedSession) {
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
                'sessions' => fn() => \App\Services\AcademicCacheService::getSessions(),
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
                'userRole' => $primaryRole,
                'can' => [
                    'manage_students' => $user->can('manage_staff'),
                    'manage_finance' => $canViewFinance,
                    'manage_results' => $user->can('manage_results'),
                    'manage_settings' => $canViewSettings,
                    'view_global_analytics' => $canViewGlobalAnalytics,
                    'view_system_status' => $canViewSystemStatus,
                ],
                'announcements' => [],
            ]);
        }

        // 3. Global Dashboard Data Caching (scoped conditionally by permissions)
        $period = $request->input('period', 'weekly');
        $cacheKey = 'admin_dashboard_global_' . ($sessionId ?? 'current') . '_' . $period . '_f' . (int)$canViewFinance . '_a' . (int)$canViewAdmissions . '_g' . (int)$canViewGlobalAnalytics . '_r' . (int)$canViewResults;

        if ($request->query('refresh') === 'true') {
            Cache::forget($cacheKey);
        }

        $globalData = Cache::remember($cacheKey, 600, function () use ($sessionId, $selectedSession, $period, $canViewFinance, $canViewAdmissions, $canViewGlobalAnalytics, $canViewResults) {
            // Calculate date range based on period selection
            $startDate = match ($period) {
                'daily' => now()->startOfDay(),
                'weekly' => now()->subDays(6)->startOfDay(),
                'monthly' => now()->subDays(29)->startOfDay(),
                'yearly' => now()->subDays(364)->startOfDay(),
                default => now()->subDays(6)->startOfDay(),
            };
            $endDate = now()->endOfDay();

            $totalStudents = 0;
            $freshStudents = 0;
            $revenue = 0;
            $revenueGrowth = 0;
            $studentGrowth = 0;
            $outstandingFees = 0;
            $registrationCompliance = 0;
            $malePercentage = 0;
            $femalePercentage = 0;
            $totalOutflow = 0;
            $netCashFlow = 0;
            $activeCoursesCount = 0;
            $applicationsCount = 0;

            // Conditional Calculations based on permissions
            if ($canViewGlobalAnalytics) {
                $totalStudents = Student::count();
                $freshStudents = Student::where('admitted_session_id', $sessionId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $previousSession = Session::where('start_date', '<', $selectedSession->start_date)
                    ->orderBy('start_date', 'desc')
                    ->first();

                if ($previousSession) {
                    $prevFresh = Student::where('admitted_session_id', $previousSession->id)->count();
                    if ($prevFresh > 0) {
                        $studentGrowth = (($freshStudents - $prevFresh) / $prevFresh) * 100;
                    }
                }

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
            }

            if ($canViewFinance) {
                $revenue = Invoice::where('session_id', $sessionId)
                    ->where('status', 'paid')
                    ->whereBetween('updated_at', [$startDate, $endDate])
                    ->sum('amount');

                $previousSession = Session::where('start_date', '<', $selectedSession->start_date)
                    ->orderBy('start_date', 'desc')
                    ->first();

                if ($previousSession) {
                    $prevRevenue = Invoice::where('session_id', $previousSession->id)->where('status', 'paid')->sum('amount');
                    if ($prevRevenue > 0) {
                        $revenueGrowth = (($revenue - $prevRevenue) / $prevRevenue) * 100;
                    }
                }

                $outstandingFees = Invoice::where('session_id', $sessionId)
                    ->where('status', '!=', 'paid')
                    ->selectRaw('SUM(amount - paid_amount) as total')
                    ->value('total') ?? 0;

                $totalOutflow = (float) Expense::where('status', 'approved')->sum('amount') + (float) Payroll::where('status', 'paid')->sum('total_amount');
                $totalInflow = (float) Invoice::where('status', 'paid')->sum('amount');
                $netCashFlow = $totalInflow - $totalOutflow;
            }

            if ($canViewResults) {
                $activeCoursesCount = CourseRegistration::where('session_id', $sessionId)
                    ->distinct('course_id')
                    ->count('course_id');
            }

            if ($canViewAdmissions) {
                $applicationsCount = User::whereHas('roles', function ($query) {
                    $query->where('name', 'applicant');
                })->whereBetween('created_at', [$startDate, $endDate])->count();
            }

            // Recent Activity (Aggregated conditionally)
            $payments = collect();
            $generatedInvoices = collect();
            if ($canViewFinance) {
                $payments = Payment::where('status', 'success')
                    ->whereHas('invoice', function ($q) use ($sessionId) {
                        $q->where(function($sq) use ($sessionId) {
                            $sq->where('session_id', $sessionId)
                              ->orWhereNull('session_id');
                        });
                    })
                    ->with(['user', 'invoice'])
                    ->latest('paid_at')
                    ->take(5)
                    ->get()
                    ->map(fn ($pay) => [
                        'id' => $pay->id,
                        'type' => 'payment',
                        'title' => 'Payment Received',
                        'description' => ($pay->user?->name ?? 'Student') . " paid " . number_format($pay->amount) . ($pay->invoice ? " for " . str_replace('_', ' ', $pay->invoice->type) : ''),
                        'amount' => $pay->amount,
                        'time_ago' => ($pay->paid_at ?? $pay->created_at)->diffForHumans(),
                        'timestamp' => $pay->paid_at ?? $pay->created_at,
                        'icon' => 'CreditCard',
                    ]);

                $generatedInvoices = Invoice::where(function($q) use ($sessionId) {
                        $q->where('session_id', $sessionId)
                          ->orWhereNull('session_id');
                    })
                    ->with(['user'])
                    ->latest('created_at')
                    ->take(5)
                    ->get()
                    ->map(fn ($inv) => [
                        'id' => $inv->id,
                        'type' => 'invoice_generated',
                        'title' => 'Invoice Generated',
                        'description' => "Invoice generated for " . ($inv->user?->name ?? 'Student') . " - " . str_replace('_', ' ', $inv->type) . " (" . number_format($inv->amount) . ")",
                        'amount' => $inv->amount,
                        'time_ago' => $inv->created_at->diffForHumans(),
                        'timestamp' => $inv->created_at,
                        'icon' => 'FileText',
                    ]);
            }

            $registrations = collect();
            if ($canViewGlobalAnalytics || $canViewAdmissions) {
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
            }

            $results = collect();
            if ($canViewResults) {
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
            }

            $recentActivity = $payments->concat($generatedInvoices)->concat($registrations)->concat($results)
                ->sortByDesc('timestamp')
                ->take(8)
                ->values()
                ->toArray();

            // Chart Data
            $revenueChart = ['labels' => [], 'data' => []];
            $combinedFinancialChart = ['labels' => [], 'inflow' => [], 'outflow' => []];
            $expenseCategoryChart = ['labels' => [], 'data' => []];

            if ($canViewFinance) {
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

                $expenseTrend = Expense::where('status', 'approved')
                    ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, SUM(amount) as total')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->keyBy('month');

                $financialTrendLabels = $revenueTrend->pluck('month')->merge($expenseTrend->pluck('month'))->unique()->sort()->values();

                $combinedFinancialChart = [
                    'labels' => $financialTrendLabels->map(fn ($m) => \Carbon\Carbon::createFromFormat('Y-m', (string) $m)->format('M'))->toArray(),
                    'inflow' => $financialTrendLabels->map(fn ($m) => $revenueTrend->firstWhere('month', $m)?->total ?? 0)->toArray(),
                    'outflow' => $financialTrendLabels->map(fn ($m) => $expenseTrend->get((string) $m)?->total ?? 0)->toArray(),
                ];

                $expenseByCategory = Expense::where('status', 'approved')
                    ->with('category')
                    ->select('expense_category_id', DB::raw('SUM(amount) as total'))
                    ->groupBy('expense_category_id')
                    ->get();

                $expenseCategoryChart = [
                    'labels' => $expenseByCategory->map(fn ($e) => $e->category?->name ?? 'Uncategorized')->toArray(),
                    'data' => $expenseByCategory->pluck('total')->toArray(),
                ];
            }

            $facultyChart = ['labels' => [], 'data' => []];
            $levelChart = ['labels' => [], 'data' => []];
            $programChart = ['labels' => [], 'data' => []];
            $staffDeptChart = ['labels' => [], 'data' => []];

            if ($canViewGlobalAnalytics) {
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

                $levelStats = Student::select('current_level', DB::raw('count(*) as total'))
                    ->whereNotNull('current_level')
                    ->groupBy('current_level')
                    ->orderBy('current_level')
                    ->get();

                $levelChart = [
                    'labels' => $levelStats->pluck('current_level')->map(fn ($l) => $l.' Lvl')->toArray(),
                    'data' => $levelStats->pluck('total')->toArray(),
                ];

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
            }

            $admissionsFunnel = ['total_applicants' => 0, 'screened_applicants' => 0, 'pending_screening' => 0];
            if ($canViewAdmissions) {
                $admissionsFunnel = [
                    'total_applicants' => $applicationsCount,
                    'screened_applicants' => User::role('applicant')->whereHas('student', function ($q) {
                        $q->whereNotNull('matriculation_number');
                    })->count(),
                    'pending_screening' => User::role('applicant')->whereDoesntHave('student')->count(),
                ];
            }

            // Cached Structural stats
            $structuralStats = [
                'faculties' => Cache::remember('faculties_count', 86400, fn() => \App\Models\Faculty::count()),
                'departments' => Cache::remember('departments_count', 86400, fn() => \App\Models\Department::count()),
                'programs' => Cache::remember('programmes_count', 86400, fn() => \App\Models\Programme::count()),
                'sessions' => Cache::remember('sessions_count', 86400, fn() => Session::count()),
                'staff' => Cache::remember('staff_count', 86400, fn() => \App\Models\Staff::count()),
                'academic_staff' => Cache::remember('academic_staff_count', 86400, fn() => \App\Models\Staff::where('is_academic', true)->count()),
                'non_academic_staff' => Cache::remember('non_academic_staff_count', 86400, fn() => \App\Models\Staff::where('is_academic', false)->count()),
            ];

            return [
                'totalStudents' => $totalStudents,
                'freshStudents' => $freshStudents,
                'revenue' => $revenue,
                'activeCoursesCount' => $activeCoursesCount,
                'applicationsCount' => $applicationsCount,
                'revenueGrowth' => $revenueGrowth,
                'studentGrowth' => $studentGrowth,
                'recentActivity' => $recentActivity,
                'revenueChart' => $revenueChart,
                'facultyChart' => $facultyChart,
                'levelChart' => $levelChart,
                'programChart' => $programChart,
                'combinedFinancialChart' => $combinedFinancialChart,
                'expenseCategoryChart' => $expenseCategoryChart,
                'staffDeptChart' => $staffDeptChart,
                'admissionsFunnel' => $admissionsFunnel,
                'outstandingFees' => $outstandingFees,
                'registrationCompliance' => $registrationCompliance,
                'genderStats' => ['male' => $malePercentage, 'female' => $femalePercentage],
                'structuralStats' => $structuralStats,
                'totalOutflow' => $totalOutflow,
                'netCashFlow' => $netCashFlow,
            ];
        });

        // Extract cached values
        $totalStudents = $globalData['totalStudents'];
        $freshStudents = $globalData['freshStudents'];
        $revenue = $globalData['revenue'];
        $activeCoursesCount = $globalData['activeCoursesCount'];
        $applicationsCount = $globalData['applicationsCount'];
        $revenueGrowth = $globalData['revenueGrowth'];
        $studentGrowth = $globalData['studentGrowth'];
        $recentActivity = collect($globalData['recentActivity']);
        $revenueChart = $globalData['revenueChart'];
        $facultyChart = $globalData['facultyChart'];
        $levelChart = $globalData['levelChart'];
        $programChart = $globalData['programChart'];
        $combinedFinancialChart = $globalData['combinedFinancialChart'];
        $expenseCategoryChart = $globalData['expenseCategoryChart'];
        $staffDeptChart = $globalData['staffDeptChart'];
        $admissionsFunnel = $globalData['admissionsFunnel'];
        $outstandingFees = $globalData['outstandingFees'];
        $registrationCompliance = $globalData['registrationCompliance'];
        $genderDistribution = $globalData['genderStats'];
        $structuralStats = $globalData['structuralStats'];
        $totalOutflow = $globalData['totalOutflow'];
        $netCashFlow = $globalData['netCashFlow'];

        // Filter Recent Activity
        $recentActivity = $canViewActivity ? $recentActivity->filter(function ($item) use ($user, $canViewFinance, $canViewAdmissions, $canViewResults) {
            // Finance Filter
            if ($item['type'] === 'payment' || $item['type'] === 'invoice_generated') {
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
            'gender_distribution' => $canViewGlobalAnalytics ? $genderDistribution : null,
            'structural' => $canViewGlobalAnalytics ? $structuralStats : null,
            'total_outflow' => $canViewFinance ? $totalOutflow : null,
            'net_cash_flow' => $canViewFinance ? $netCashFlow : null,
            'admissions_funnel' => $canViewAdmissions ? $admissionsFunnel : null,
            'active_students' => $canViewGlobalAnalytics ? $totalStudents : null,
        ];

        // 7. My Course Allocations & Timetable (If Staff)
        $myAllocations = collect();
        $myTimetable = collect();
        $courseIds = [];
        if ($user->hasAnyRole(['staff', 'lecturer', 'course_coordinator', 'dean', 'hod', 'registrar', 'bursar', 'finance_officer', 'admissions_officer', 'admissions_manager'])) {
            if (!$user->staff) {
                $isAcademic = $user->hasAnyRole(['lecturer', 'dean', 'hod', 'course_coordinator']);
                \App\Models\Staff::create([
                    'user_id' => $user->id,
                    'staff_number' => \App\Helpers\StaffNumberHelper::generate(),
                    'is_academic' => $isAcademic,
                ]);
                $user->load('staff');
            }

            $myAllocations = \App\Models\CourseAllocation::whereHas('staff', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->where('session_id', $sessionId)
                ->with(['course'])
                ->get();

            $courseIds = $myAllocations->pluck('course_id')->toArray();

            // Fetch Timetable from Cache
            if ($user->staff) {
                $fetchedTimetable = \App\Services\AcademicCacheService::getStaffTimetable($user->staff->id, $sessionId);
                $myTimetable = is_array($fetchedTimetable) ? collect($fetchedTimetable) : $fetchedTimetable;
            }

            // Lecturer Stats
            $lecturerStats = [
                'total_students' => \App\Models\CourseRegistration::whereIn('course_id', $courseIds)
                    ->where('session_id', $sessionId)
                    ->distinct('student_id')
                    ->count('student_id'),
                'total_courses' => $myAllocations->count(),
                'classes_today' => $myTimetable->filter(function ($t) {
                    $day = is_object($t) ? ($t->day ?? '') : ($t['day'] ?? '');
                    return strtolower($day) === strtolower(now()->format('l'));
                })->count(),
            ];
        }
        
        // 9. Overwrite stats for Lecturers/Allocated Staff (Strictly their own data)
        if (!$canViewGlobalAnalytics && isset($lecturerStats)) {
            $dashboardStats['total_students'] = $lecturerStats['total_students'];
            $dashboardStats['active_courses'] = $lecturerStats['total_courses'];
            $dashboardStats['active_students'] = $lecturerStats['total_students'];
        }

        $announcements = Cache::remember('staff_dashboard_bulletins', 60 * 10, function () {
            return \App\Models\Bulletin::with('author')
                ->whereIn('target_audience', ['all', 'staff'])
                ->orderBy('is_pinned', 'desc')
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get();
        });

        return Inertia::render('Admin/Dashboard', [
            'currentSessionName' => $selectedSession->name,
            'filters' => [
                'session_id' => $sessionId,
                'period' => $period,
            ],
            'sessions' => fn() => \App\Services\AcademicCacheService::getSessions(),
            'stats' => $dashboardStats,
            'lecturerStats' => $lecturerStats ?? null,
            'recentActivity' => $recentActivity,
            'announcements' => $announcements,
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
                'manage_settings' => $canViewSettings,
                'view_global_analytics' => $canViewGlobalAnalytics,
                'view_system_status' => $canViewSystemStatus,
            ],
        ]);
    }
}
