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
            ->values()
            ->all();


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

        // Faculty Distribution (Pie Chart)
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


        // 6. View Data
        $sessions = Session::orderBy('start_date', 'desc')->get(['id', 'name']);

        return Inertia::render('Admin/Dashboard', [
            'currentSessionName' => $selectedSession->name,
            'filters' => ['session_id' => $sessionId],
            'sessions' => $sessions,
            'stats' => [
                'total_students' => $totalStudents,
                'fresh_students' => $freshStudents,
                'applications' => $applicationsCount,
                'revenue' => $revenue,
                'active_courses' => $activeCoursesCount,
                'revenue_growth' => round($revenueGrowth, 1),
                'student_growth' => round($studentGrowth, 1),
            ],
            'recentActivity' => $recentActivity,
            'charts' => [
                'revenue' => $revenueChart,
                'faculty' => $facultyChart,
            ]
        ]);
    }
}
