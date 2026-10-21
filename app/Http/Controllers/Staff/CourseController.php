<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Session;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $currentSession = Session::current();

        $filters = $request->only(['session_id', 'search']);
        $sessionId = $filters['session_id'] ?? $currentSession?->id;
        if ($sessionId === 'all') {
            $sessionId = null;
        }

        $allocations = CourseAllocation::query()
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('course', function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->with(['course', 'session'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Staff/Course/Index', [
            'allocations' => $allocations,
            'sessions' => Session::orderBy('start_date', 'desc')->get(['id', 'name']),
            'filters' => $filters,
            'currentSessionId' => $currentSession?->id,
        ]);
    }

    public function show(Course $course)
    {
        $user = auth()->user();
        $currentSession = Session::current();

        // 1. Authorization: Ensure user is allocated to this course (and optionally current session)
        // We can loosen the session check if we want them to see history, but for now let's stick to current/active.
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        // Allow admin bypass or strict check? Let's do strict for "Staff" namespace.
        if (!$isAllocated && !$user->hasRole('admin')) {
            abort(403, 'You are not assigned to this course.');
        }

        // 2. Fetch Registered Students
        // Assuming CourseRegistration model links student and course
        $registrations = CourseRegistration::where('course_id', $course->id)
            ->where('session_id', $currentSession->id)
            ->with(['student.user:id,name,email', 'student:id,user_id,matriculation_number,department_id,current_level', 'student.department:id,name'])
            ->latest()
            ->get()
            ->map(fn($reg) => [
                'id' => $reg->id,
                'student_name' => $reg->student->user->name,
                'matriculation_number' => $reg->student->matriculation_number,
                'email' => $reg->student->user->email,
                'department' => $reg->student->department->name,
                'level' => $reg->student->current_level,
                'registered_at' => $reg->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Staff/Course/Show', [
            'course' => $course->only(['id', 'code', 'title', 'units']),
            'session' => $currentSession ? $currentSession->only(['id', 'name']) : ['id' => '', 'name' => 'Current Session'],
            'students' => $registrations,
            'importSummary' => fn () => session('import_summary'),
        ]);
    }

    public function uploadStudents(Request $request, Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'skip_existing' => 'nullable|boolean',
        ]);

        $session = Session::current() ?? Session::latest()->first();
        if (! $session) {
            return back()->with('error', 'Active academic session not found.');
        }

        $skipExisting = $request->boolean('skip_existing', true);

        try {
            $import = new \App\Imports\CourseStudentImport($course, $session, null, $skipExisting);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $stats = $import->getStats();
            $msg = "Import completed: {$stats['enrolled']} student(s) enrolled.";
            if ($stats['skipped'] > 0) {
                $msg .= " {$stats['skipped']} skipped.";
            }

            return back()
                ->with('import_summary', $stats)
                ->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Error during upload: ' . $e->getMessage());
        }
    }

    public function downloadStudentTemplate(Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $headers = ['matriculation_number', 'student_name'];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['MAT/2026/001', 'John Doe']);
            fputcsv($file, ['MAT/2026/002', 'Jane Smith']);
            fclose($file);
        };

        $fileName = "student_upload_template_{$course->code}.csv";

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    public function exportStudents(Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $currentSession = Session::current() ?? Session::latest()->first();
        $registrations = CourseRegistration::where('course_id', $course->id)
            ->when($currentSession, fn($q) => $q->where('session_id', $currentSession->id))
            ->with(['student.user', 'student.department'])
            ->latest()
            ->get();

        $headers = ['Matric Number', 'Student Name', 'Email', 'Department', 'Level', 'Registered On'];

        $callback = function () use ($headers, $registrations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($registrations as $reg) {
                fputcsv($file, [
                    $reg->student?->matriculation_number ?? 'N/A',
                    $reg->student?->user?->name ?? 'Unknown',
                    $reg->student?->user?->email ?? 'N/A',
                    $reg->student?->department?->name ?? 'N/A',
                    $reg->student?->current_level ?? 'N/A',
                    $reg->created_at ? $reg->created_at->format('Y-m-d') : 'N/A',
                ]);
            }
            fclose($file);
        };

        $fileName = "students_{$course->code}_" . now()->format('Y_m_d') . ".csv";

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
