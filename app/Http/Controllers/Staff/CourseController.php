<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Imports\CourseAttendanceImport;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseLectureSession;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Programme;
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
        $currentSession = Session::current() ?? Session::latest()->first();

        // 1. Authorization: Ensure user is allocated to this course (and optionally current session)
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        // Allow admin bypass or strict check for "Staff" namespace
        if (!$isAllocated && !$user->hasRole('admin')) {
            abort(403, 'You are not assigned to this course.');
        }

        // 2. Fetch Registered Students with Department and Programme eager-loaded
        $registrations = CourseRegistration::where('course_id', $course->id)
            ->when($currentSession, fn($q) => $q->where('session_id', $currentSession->id))
            ->with([
                'student.user:id,name,email',
                'student:id,user_id,matriculation_number,department_id,program_id,current_level',
                'student.department:id,name',
                'student.program:id,name',
            ])
            ->latest()
            ->get();

        $students = $registrations->map(fn($reg) => [
            'id' => $reg->id,
            'student_id' => $reg->student?->id,
            'student_name' => $reg->student?->user?->name ?? 'Unknown',
            'matriculation_number' => $reg->student?->matriculation_number ?? 'N/A',
            'email' => $reg->student?->user?->email ?? 'N/A',
            'department_id' => $reg->student?->department_id,
            'department' => $reg->student?->department?->name ?? 'Unassigned',
            'program_id' => $reg->student?->program_id,
            'program' => $reg->student?->program?->name ?? 'Unassigned',
            'level' => $reg->student?->current_level ?? 'N/A',
            'registered_at' => $reg->created_at ? $reg->created_at->format('M d, Y') : 'N/A',
        ]);

        // Aggregate unique departments, programs, and levels for fast client-side filtering
        $departments = $students
            ->filter(fn($s) => !empty($s['department_id']))
            ->groupBy('department_id')
            ->map(fn($items, $deptId) => [
                'id' => (string) $deptId,
                'name' => $items->first()['department'],
                'count' => $items->count(),
            ])
            ->sortBy('name')
            ->values();

        $programs = $students
            ->filter(fn($s) => !empty($s['program_id']))
            ->groupBy('program_id')
            ->map(fn($items, $progId) => [
                'id' => (string) $progId,
                'name' => $items->first()['program'],
                'count' => $items->count(),
            ])
            ->sortBy('name')
            ->values();

        $levels = $students
            ->pluck('level')
            ->filter(fn($lvl) => !empty($lvl) && $lvl !== 'N/A')
            ->unique()
            ->sort()
            ->values();

        // 3. Fetch Recorded Attendance History for this Course with Lecture Session details
        $attendanceHistory = [];
        if ($currentSession) {
            $lectureSessions = CourseLectureSession::where('course_id', $course->id)
                ->where('session_id', $currentSession->id)
                ->orderBy('lecture_date', 'desc')
                ->get()
                ->keyBy(fn($s) => $s->lecture_date instanceof \Carbon\CarbonInterface ? $s->lecture_date->format('Y-m-d') : substr((string)$s->lecture_date, 0, 10));

            $attendanceStats = StudentCourseAttendance::where('course_id', $course->id)
                ->where('session_id', $currentSession->id)
                ->select('attendance_date')
                ->selectRaw('count(*) as total_records')
                ->selectRaw("sum(case when status = 'present' then 1 else 0 end) as present_count")
                ->selectRaw("sum(case when status = 'absent' then 1 else 0 end) as absent_count")
                ->groupBy('attendance_date')
                ->orderBy('attendance_date', 'desc')
                ->get();

            $allDates = $attendanceStats->pluck('attendance_date')
                ->map(fn($d) => $d instanceof \Carbon\CarbonInterface ? $d->format('Y-m-d') : substr((string)$d, 0, 10))
                ->merge($lectureSessions->keys())
                ->unique()
                ->sortDesc()
                ->values();

            $attendanceHistory = $allDates->map(function ($dateStr) use ($attendanceStats, $lectureSessions) {
                $stat = $attendanceStats->first(fn($s) => ($s->attendance_date instanceof \Carbon\CarbonInterface ? $s->attendance_date->format('Y-m-d') : substr((string)$s->attendance_date, 0, 10)) === $dateStr);
                $session = $lectureSessions->get($dateStr);
                $totalRecords = (int) ($stat?->total_records ?? 0);
                $presentCount = (int) ($stat?->present_count ?? 0);
                $absentCount = (int) ($stat?->absent_count ?? 0);
                $dateObj = \Carbon\Carbon::parse($dateStr);

                return [
                    'id' => $session?->id,
                    'date' => $dateStr,
                    'formatted_date' => $dateObj->format('M d, Y'),
                    'total_records' => $totalRecords,
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'attendance_rate' => $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0,
                    'department_ids' => $session?->department_ids,
                    'department_names' => $session?->department_names ?? [],
                    'is_all_departments' => empty($session?->department_ids),
                    'notes' => $session?->notes,
                ];
            })->values();
        }

        return Inertia::render('Staff/Course/Show', [
            'course' => $course->only(['id', 'code', 'title', 'units']),
            'session' => $currentSession ? $currentSession->only(['id', 'name']) : ['id' => '', 'name' => 'Current Session'],
            'students' => $students,
            'departments' => $departments,
            'programs' => $programs,
            'levels' => $levels,
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

    public function exportStudents(Request $request, Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $currentSession = Session::current() ?? Session::latest()->first();
        $query = CourseRegistration::where('course_id', $course->id)
            ->when($currentSession, fn($q) => $q->where('session_id', $currentSession->id))
            ->with(['student.user', 'student.department', 'student.program'])
            ->latest();

        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->whereHas('student', fn($sq) => $sq->where('department_id', $request->department_id));
        }
        if ($request->filled('program_id') && $request->program_id !== 'all') {
            $query->whereHas('student', fn($sq) => $sq->where('program_id', $request->program_id));
        }
        if ($request->filled('level') && $request->level !== 'all') {
            $query->whereHas('student', fn($sq) => $sq->where('current_level', $request->level));
        }

        $registrations = $query->get();

        $headers = ['Matric Number', 'Student Name', 'Email', 'Department', 'Programme', 'Level', 'Registered On'];

        $callback = function () use ($headers, $registrations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($registrations as $reg) {
                fputcsv($file, [
                    $reg->student?->matriculation_number ?? 'N/A',
                    $reg->student?->user?->name ?? 'Unknown',
                    $reg->student?->user?->email ?? 'N/A',
                    $reg->student?->department?->name ?? 'N/A',
                    $reg->student?->program?->name ?? 'N/A',
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
     * Download pre-populated CSV template with registered students for this course.
     * Optionally filtered by department cohort.
     */
    public function downloadAttendanceTemplate(Request $request, Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $currentSession = Session::current() ?? Session::latest()->first();

        $deptParam = $request->input('department_ids');
        $deptIds = [];
        if (is_array($deptParam)) {
            $deptIds = array_filter($deptParam);
        } elseif (is_string($deptParam) && trim($deptParam) !== '') {
            $deptIds = array_filter(explode(',', $deptParam));
        }

        $query = CourseRegistration::where('course_id', $course->id)
            ->when($currentSession, fn($q) => $q->where('session_id', $currentSession->id))
            ->with(['student.user', 'student.department']);

        if (!empty($deptIds)) {
            $query->whereHas('student', fn($sq) => $sq->whereIn('department_id', $deptIds));
        }

        $registrations = $query->get();

        $headers = ['matriculation_number', 'student_name', 'department', 'status'];

        $callback = function () use ($headers, $registrations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            if ($registrations->isEmpty()) {
                // Example rows if no students registered yet
                fputcsv($file, ['MAT/2026/001', 'Sample Student 1', 'Computer Science', 'Present']);
                fputcsv($file, ['MAT/2026/002', 'Sample Student 2', 'Software Engineering', 'Absent']);
            } else {
                foreach ($registrations as $reg) {
                    fputcsv($file, [
                        $reg->student?->matriculation_number ?? 'N/A',
                        $reg->student?->user?->name ?? 'Unknown',
                        $reg->student?->department?->name ?? 'N/A',
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
     * Upload Excel/CSV attendance sheet for a specific lecture date with optional department cohort targeting.
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
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'string|exists:departments,id',
            'notes' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.mimes' => 'The file must be a spreadsheet or CSV file (.xlsx, .xls, .csv).',
        ]);

        $session = Session::current() ?? Session::latest()->first();
        if (!$session) {
            return back()->with('error', 'Active academic session not found.');
        }

        $deptIds = $request->input('department_ids');
        $deptIds = is_array($deptIds) ? array_values(array_filter($deptIds)) : null;
        if (empty($deptIds)) {
            $deptIds = null;
        }

        $deptNames = null;
        if ($deptIds) {
            $deptNames = Department::whereIn('id', $deptIds)->pluck('name')->toArray();
        }

        // Create or update CourseLectureSession
        $lectureSession = CourseLectureSession::updateOrCreate(
            [
                'course_id' => $course->id,
                'session_id' => $session->id,
                'lecture_date' => $request->input('attendance_date'),
            ],
            [
                'department_ids' => $deptIds,
                'department_names' => $deptNames,
                'notes' => $request->input('notes'),
                'recorded_by' => $user->id,
            ]
        );

        try {
            $import = new CourseAttendanceImport(
                $course,
                $session,
                $request->input('attendance_date'),
                $user->id,
                $lectureSession->id
            );
            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();
            $deptText = $deptNames ? ' (' . implode(', ', $deptNames) . ')' : ' (All Enrolled Departments)';
            $msg = "Attendance for {$request->input('attendance_date')}{$deptText} recorded: {$stats['processed']} student(s) logged ({$stats['present']} Present, {$stats['absent']} Absent).";
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
     * Delete recorded attendance and lecture session for a specific lecture date.
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

        CourseLectureSession::where('course_id', $course->id)
            ->where('session_id', $session->id)
            ->whereDate('lecture_date', $date)
            ->delete();

        $deleted = StudentCourseAttendance::where('course_id', $course->id)
            ->where('session_id', $session->id)
            ->whereDate('attendance_date', $date)
            ->delete();

        return back()->with('success', "Attendance records for {$date} removed ({$deleted} entries deleted).");
    }

    /**
     * Export complete attendance matrix (Dates as columns, Students as rows).
     * Optionally filtered by department.
     */
    public function exportAttendance(Request $request, Course $course)
    {
        $user = auth()->user();
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (!$isAllocated && !$user->hasRole('admin') && !$user->can('manage_courses')) {
            abort(403, 'You are not assigned to this course.');
        }

        $session = Session::current() ?? Session::latest()->first();

        $deptId = $request->query('department_id');
        $regQuery = CourseRegistration::where('course_id', $course->id)
            ->when($session, fn($q) => $q->where('session_id', $session->id))
            ->with(['student.user', 'student.department', 'attendances' => fn($q) => $q->where('course_id', $course->id)->where('session_id', $session?->id)]);

        if ($deptId && $deptId !== 'all') {
            $regQuery->whereHas('student', fn($sq) => $sq->where('department_id', $deptId));
        }

        $registrations = $regQuery->get();

        // Get unique dates sorted chronologically
        $dates = StudentCourseAttendance::where('course_id', $course->id)
            ->when($session, fn($q) => $q->where('session_id', $session->id))
            ->distinct()
            ->orderBy('attendance_date', 'asc')
            ->pluck('attendance_date')
            ->map(fn($d) => $d instanceof \Carbon\CarbonInterface ? $d->format('Y-m-d') : substr((string)$d, 0, 10))
            ->toArray();

        $headers = array_merge(['Matric Number', 'Student Name', 'Department'], $dates, ['Attended', 'Total Classes', 'Attendance %']);

        $callback = function () use ($headers, $dates, $registrations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            $totalDates = count($dates);

            foreach ($registrations as $reg) {
                $attMap = $reg->attendances->keyBy(fn($a) => $a->attendance_date instanceof \Carbon\CarbonInterface ? $a->attendance_date->format('Y-m-d') : substr((string)$a->attendance_date, 0, 10));

                $row = [
                    $reg->student?->matriculation_number ?? 'N/A',
                    $reg->student?->user?->name ?? 'Unknown',
                    $reg->student?->department?->name ?? 'N/A',
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
