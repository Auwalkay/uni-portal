<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Imports\CourseAttendanceImport;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Session;
use App\Models\StudentCourseAttendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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

        // 3. Fetch Recorded Attendance History for this Course
        $attendanceHistory = [];
        if ($currentSession) {
            $attendanceHistory = StudentCourseAttendance::where('course_id', $course->id)
                ->where('session_id', $currentSession->id)
                ->select('attendance_date')
                ->selectRaw('count(*) as total_records')
                ->selectRaw("sum(case when status = 'present' then 1 else 0 end) as present_count")
                ->selectRaw("sum(case when status = 'absent' then 1 else 0 end) as absent_count")
                ->groupBy('attendance_date')
                ->orderBy('attendance_date', 'desc')
                ->get()
                ->map(fn($row) => [
                    'date' => $row->attendance_date->format('Y-m-d'),
                    'formatted_date' => $row->attendance_date->format('M d, Y'),
                    'total_records' => (int) $row->total_records,
                    'present_count' => (int) $row->present_count,
                    'absent_count' => (int) $row->absent_count,
                    'attendance_rate' => $row->total_records > 0 ? round(($row->present_count / $row->total_records) * 100, 1) : 0,
                ]);
        }

        return Inertia::render('Staff/Course/Show', [
            'course' => $course->only(['id', 'code', 'title', 'units']),
            'session' => $currentSession ? $currentSession->only(['id', 'name']) : ['id' => '', 'name' => 'Current Session'],
            'students' => $registrations,
            'attendanceHistory' => $attendanceHistory,
            'importSummary' => fn () => session('import_summary'),
            'attendanceSummary' => fn () => session('attendance_summary'),
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
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'skip_existing' => 'nullable|boolean',
        ], [
            'file.mimes' => 'The file must be a spreadsheet or CSV file (.xlsx, .xls, .csv).',
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

    /**
     * Download pre-populated CSV template with all currently registered students for this course.
     */
    public function downloadAttendanceTemplate(Course $course)
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
            ->with(['student.user'])
            ->get();

        $headers = ['matriculation_number', 'student_name', 'status'];

        $callback = function () use ($headers, $registrations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            if ($registrations->isEmpty()) {
                // Example rows if no students registered yet
                fputcsv($file, ['MAT/2026/001', 'Sample Student 1', 'Present']);
                fputcsv($file, ['MAT/2026/002', 'Sample Student 2', 'Absent']);
            } else {
                foreach ($registrations as $reg) {
                    fputcsv($file, [
                        $reg->student?->matriculation_number ?? 'N/A',
                        $reg->student?->user?->name ?? 'Unknown',
                        'Present',
                    ]);
                }
            }
            fclose($file);
        };

        $fileName = "attendance_template_{$course->code}_" . now()->format('Y_m_d') . ".csv";

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    /**
     * Upload Excel/CSV attendance sheet for a specific lecture date.
     */
    public function uploadAttendance(Request $request, Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $request->validate([
            'attendance_date' => 'required|date',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.mimes' => 'The file must be a spreadsheet or CSV file (.xlsx, .xls, .csv).',
        ]);

        $session = Session::current() ?? Session::latest()->first();
        if (!$session) {
            return back()->with('error', 'Active academic session not found.');
        }

        try {
            $import = new CourseAttendanceImport($course, $session, $request->input('attendance_date'), $user->id);
            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();
            $msg = "Attendance for {$request->input('attendance_date')} recorded: {$stats['processed']} student(s) logged ({$stats['present']} Present, {$stats['absent']} Absent).";
            if ($stats['unregistered'] > 0) {
                $msg .= " {$stats['unregistered']} unregistered student(s) skipped.";
            }

            return back()
                ->with('attendance_summary', $stats)
                ->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Error during attendance upload: ' . $e->getMessage());
        }
    }

    /**
     * Delete recorded attendance for a specific lecture date.
     */
    public function deleteAttendanceDate(Course $course, string $date)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $session = Session::current() ?? Session::latest()->first();
        if (!$session) {
            return back()->with('error', 'Active academic session not found.');
        }

        $deleted = StudentCourseAttendance::where('course_id', $course->id)
            ->where('session_id', $session->id)
            ->whereDate('attendance_date', $date)
            ->delete();

        return back()->with('success', "Attendance records for {$date} removed ({$deleted} entries deleted).");
    }

    /**
     * Export complete attendance matrix (Dates as columns, Students as rows).
     */
    public function exportAttendance(Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $session = Session::current() ?? Session::latest()->first();
        $registrations = CourseRegistration::where('course_id', $course->id)
            ->when($session, fn($q) => $q->where('session_id', $session->id))
            ->with(['student.user', 'attendances' => fn($q) => $q->where('course_id', $course->id)->where('session_id', $session?->id)])
            ->get();

        // Get unique dates sorted chronologically
        $dates = StudentCourseAttendance::where('course_id', $course->id)
            ->when($session, fn($q) => $q->where('session_id', $session->id))
            ->distinct()
            ->orderBy('attendance_date', 'asc')
            ->pluck('attendance_date')
            ->map(fn($d) => $d instanceof \Carbon\CarbonInterface ? $d->format('Y-m-d') : substr((string)$d, 0, 10))
            ->toArray();

        $headers = array_merge(['Matric Number', 'Student Name'], $dates, ['Attended', 'Total Classes', 'Attendance %']);

        $callback = function () use ($headers, $dates, $registrations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            $totalDates = count($dates);

            foreach ($registrations as $reg) {
                $attMap = $reg->attendances->keyBy(fn($a) => $a->attendance_date instanceof \Carbon\CarbonInterface ? $a->attendance_date->format('Y-m-d') : substr((string)$a->attendance_date, 0, 10));

                $row = [
                    $reg->student?->matriculation_number ?? 'N/A',
                    $reg->student?->user?->name ?? 'Unknown',
                ];

                $attendedCount = 0;
                foreach ($dates as $d) {
                    $att = $attMap->get($d);
                    if ($att) {
                        $isPres = ($att->status->value ?? $att->status) === 'present';
                        $row[] = $isPres ? 'P' : 'A';
                        if ($isPres) $attendedCount++;
                    } else {
                        $row[] = '-';
                    }
                }

                $pct = $totalDates > 0 ? round(($attendedCount / $totalDates) * 100, 1) . '%' : 'N/A';
                $row[] = $attendedCount;
                $row[] = $totalDates;
                $row[] = $pct;

                fputcsv($file, $row);
            }

            fclose($file);
        };

        $fileName = "attendance_matrix_{$course->code}_" . now()->format('Y_m_d') . ".csv";

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
