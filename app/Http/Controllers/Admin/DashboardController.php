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
        // Default to current session if not provided
        $currentSession = Session::current();
        $sessionId = $request->input('session_id', $currentSession?->id);
        $selectedSession = Session::find($sessionId) ?? $currentSession;

        // Stats 1: Total Students (Active) vs Fresh Students (Admitted in this session)
        $totalStudents = Student::count();
        $freshStudents = Student::where('admitted_session_id', $sessionId)->count();

        // Stats 2: Revenue (Paid School Fees & Acceptance Fees in this session)
        // We assume invoices linked to the session
        $revenue = Invoice::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->sum('amount');

        // Optional: Compare with previous session revenue if needed for "trend"
        // For now, simple sum.

        // Stats 3: Admissions / Applications
        // Assuming we have an Applicant model or User with role 'applicant'
        // Let's count Users who are applicants created in this timeframe or linked to session if possible.
        // For now, simpler metric: Total Users with role 'student' created in this session timeframe?
        // Or if we have an Application model (we saw ApplicationController).
        // Let's use a placeholder count based on Students for now if Application model isn't clear,
        // but checking file list, there is `App\Http\Controllers\Applicant\ApplicationController` so likely an `Application` model exists?
        // Let's assume `Student` count serves as a proxy for successful admissions for now.
        // Actually, let's look for an Application or Admission model. 
        // I'll stick to 'freshStudents' as 'New Admissions' for now.

        // Stats 4: Active Courses (Registered courses in this session)
        $activeCoursesCount = CourseRegistration::where('session_id', $sessionId)
            ->distinct('course_id')
            ->count('course_id');

        // Recent Activity (Mixed bag)
        // 1. New Students
        $recentStudents = Student::with('user')
            ->where('admitted_session_id', $sessionId)
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($student) {
                return [
                    'type' => 'student',
                    'title' => 'New student registration',
                    'description' => $student->user->name . ' joined ' . $student->program,
                    'time' => $student->created_at->diffForHumans(),
                    'icon' => 'Users'
                ];
            });

        // 2. Recent Payments
        $recentPayments = Invoice::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->latest('updated_at')
            ->take(3)
            ->get()
            ->map(function ($invoice) {
                return [
                    'type' => 'payment',
                    'title' => 'Payment received',
                    'description' => ucfirst(str_replace('_', ' ', $invoice->type)) . ' paid',
                    'time' => $invoice->updated_at->diffForHumans(),
                    'icon' => 'CreditCard'
                ];
            });

        $recentActivity = $recentStudents->toBase()->merge($recentPayments->toBase())->sortByDesc('time')->values()->take(5);

        // Chart 1: Revenue Trend (Monthly for the session)
        // Group invoices by month and sum amount
        $revenueTrendDef = Invoice::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $revenueLabels = $revenueTrendDef->pluck('month')->map(fn($m) => \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M Y'))->toArray();
        $revenueData = $revenueTrendDef->pluck('total')->toArray();

        // Chart 2: Student Distribution by Faculty
        $facultyStats = Student::select('faculties.name', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->join('departments', 'students.department_id', '=', 'departments.id')
            ->join('faculties', 'departments.faculty_id', '=', 'faculties.id')
            ->groupBy('faculties.name')
            ->get();

        $facultyLabels = $facultyStats->pluck('name')->toArray();
        $facultyData = $facultyStats->pluck('total')->toArray();

        // Chart 3: Registration Status (Registered vs Unregistered students in this session)
        // Registered means they have at least one course registration in this session
        $registeredCount = Student::whereHas('registrations', function ($query) use ($sessionId) {
            $query->where('session_id', $sessionId);
        })->count();

        $unregisteredCount = $totalStudents - $registeredCount;

        $registrationStats = [
            'labels' => ['Registered', 'Unregistered'],
            'data' => [$registeredCount, $unregisteredCount],
        ];

        // Get all sessions for the filter dropdown
        $sessions = Session::orderBy('start_date', 'desc')->get(['id', 'name']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'fresh_students' => $freshStudents,
                'revenue' => $revenue,
                'active_courses' => $activeCoursesCount,
            ],
            'recentActivity' => $recentActivity,
            'sessions' => $sessions,
            'filters' => [
                'session_id' => $sessionId,
            ],
            'currentSessionName' => $selectedSession?->name,
            'charts' => [
                'revenue' => [
                    'labels' => $revenueLabels,
                    'data' => $revenueData,
                ],
                'faculty' => [
                    'labels' => $facultyLabels,
                    'data' => $facultyData,
                ],
                'registration' => $registrationStats,
            ],
        ]);
    }
}
