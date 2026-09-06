<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\ExamAttendance;
use App\Models\ExamIncident;
use App\Models\ExamInvigilator;
use App\Models\ExamSchedule;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Services\AcademicCacheService;
use App\Services\ExamManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamScheduleController extends Controller
{
    public function __construct(protected ExamManagementService $examService)
    {
    }

    public function index(Request $request)
    {
        $currentSession = AcademicCacheService::getCurrentSession();
        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedSemesterId = $request->input('semester_id');
        $selectedDepartmentId = $request->input('department_id');
        $selectedLevel = $request->input('level');
        $examType = $request->input('exam_type');
        $search = $request->input('search');

        $query = ExamSchedule::with([
                'course',
                'department',
                'session',
                'semester',
                'invigilators.staff.user',
                'incidents.student.user',
                'incidents.invigilator.user',
            ])
            ->withCount(['attendances'])
            ->when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))
            ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
            ->when($selectedDepartmentId, fn ($q) => $q->where('department_id', $selectedDepartmentId))
            ->when($selectedLevel, fn ($q) => $q->where('level', $selectedLevel))
            ->when($examType, fn ($q) => $q->where('exam_type', $examType))
            ->when($search, function ($q, $search) {
                $q->where('reference_id', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhereHas('course', fn ($cq) => $cq->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
            });

        $schedules = $query->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Delegate conflict detection to ExamManagementService
        $conflicts = $this->examService->detectVenueConflicts($selectedSessionId);

        // Calculate KPI metrics
        $totalExams = ExamSchedule::when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))->count();
        $totalInvigilators = ExamInvigilator::whereHas('schedule', fn ($q) => $selectedSessionId ? $q->where('session_id', $selectedSessionId) : $q)->count();
        $totalIncidents = ExamIncident::whereHas('schedule', fn ($q) => $selectedSessionId ? $q->where('session_id', $selectedSessionId) : $q)->count();
        $totalCapacity = ExamSchedule::when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))->sum('max_capacity');

        return Inertia::render('Admin/Exams/Index', [
            'schedules' => $schedules,
            'sessions' => AcademicCacheService::getSessions() ?? Session::orderBy('name', 'desc')->get(),
            'semesters' => AcademicCacheService::getSemesters() ?? Semester::orderBy('name', 'asc')->get(),
            'departments' => AcademicCacheService::getAcademicDepartments() ?? Department::orderBy('name', 'asc')->get(),
            'courses' => Course::select('id', 'code', 'title', 'department_id', 'level')->orderBy('code', 'asc')->get(),
            'staff' => Staff::with('user:id,name')->select('id', 'user_id', 'staff_number')->get()->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->user?->name ?? 'Staff Member',
                'staff_number' => $s->staff_number ?? 'N/A',
            ]),
            'students' => Student::with('user:id,name')->select('id', 'user_id', 'matriculation_number')->limit(100)->get()->map(fn ($st) => [
                'id' => $st->id,
                'name' => $st->user?->name ?? 'Student',
                'matric_number' => $st->matric_number ?? 'N/A',
            ]),
            'stats' => [
                'total_exams' => $totalExams,
                'total_invigilators' => $totalInvigilators,
                'total_incidents' => $totalIncidents,
                'total_capacity' => $totalCapacity,
                'conflicts_count' => count($conflicts),
            ],
            'conflicts' => $conflicts,
            'filters' => $request->only(['session_id', 'semester_id', 'department_id', 'level', 'exam_type', 'search']),
            'isPublished' => filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function togglePublish()
    {
        $current = filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN);
        $new = !$current;
        \App\Models\SystemSetting::set('publish_exam_timetable', $new ? '1' : '0');
        AcademicCacheService::clearAll();

        $statusStr = $new ? 'published to students' : 'hidden from students (draft mode)';
        return back()->with('success', "Exam timetable is now {$statusStr}.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'exam_type' => 'required|string|in:final,mid_term,cbt,resit',
            'max_capacity' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
        ]);

        $validated['reference_id'] = 'EXM-' . strtoupper(substr(uniqid(), -6));
        $validated['created_by'] = auth()->id();

        ExamSchedule::create($validated);

        return back()->with('success', 'Exam schedule created successfully.');
    }

    public function update(Request $request, ExamSchedule $exam)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'exam_type' => 'required|string|in:final,mid_term,cbt,resit',
            'max_capacity' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
        ]);

        $exam->update($validated);

        return back()->with('success', 'Exam schedule updated successfully.');
    }

    public function destroy(ExamSchedule $exam)
    {
        $exam->delete();

        return back()->with('success', 'Exam schedule deleted successfully.');
    }

    public function assignInvigilator(Request $request, ExamSchedule $exam)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'role' => 'required|string|in:chief,assistant',
        ]);

        ExamInvigilator::updateOrCreate(
            [
                'exam_schedule_id' => $exam->id,
                'staff_id' => $validated['staff_id'],
            ],
            [
                'role' => $validated['role'],
                'status' => 'assigned',
            ]
        );

        return back()->with('success', 'Invigilator assigned successfully.');
    }

    public function removeInvigilator(ExamInvigilator $invigilator)
    {
        $invigilator->delete();

        return back()->with('success', 'Invigilator removed successfully.');
    }

    public function logIncident(Request $request, ExamSchedule $exam)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invigilator_id' => 'nullable|exists:staff,id',
            'incident_type' => 'required|string|in:malpractice,contraband,medical,impersonation,absenteeism,other',
            'description' => 'required|string',
            'status' => 'required|string|in:logged,under_investigation,resolved,sanctioned',
            'action_taken' => 'nullable|string',
        ]);

        $validated['exam_schedule_id'] = $exam->id;
        $validated['reference_id'] = 'INC-' . strtoupper(substr(uniqid(), -6));
        $validated['logged_by'] = auth()->id();

        ExamIncident::create($validated);

        return back()->with('success', 'Exam incident logged successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'file' => 'required|file|mimes:csv,txt,xls,xlsx',
        ]);

        try {
            $importer = new \App\Imports\ExamScheduleImport($request->session_id, $request->semester_id);
            $stats = $importer->process($request->file('file')->getRealPath());

            $msg = "Bulk Exam Import Complete: {$stats['created']} exams scheduled, {$stats['updated']} updated, {$stats['invigilators_assigned']} invigilator assignments created.";
            if ($stats['skipped'] > 0) {
                $msg .= " ({$stats['skipped']} skipped/errors)";
            }

            if (count($stats['errors']) > 0) {
                return back()->with('warning', $msg . ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 4)));
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Exam import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="semester_exam_timetable_template.csv"',
        ];

        $columns = [
            'course_code',
            'exam_date',
            'start_time',
            'end_time',
            'venue',
            'max_capacity',
            'exam_type',
            'chief_invigilator_staff_number',
            'assistant_invigilator_1_staff_number',
            'assistant_invigilator_2_staff_number',
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Sample Rows
            fputcsv($file, ['CSC101', '2026-10-15', '09:00', '12:00', 'Multipurpose Hall A', '150', 'final', 'STF-001', 'STF-002', 'STF-003']);
            fputcsv($file, ['MTH101', '2026-10-16', '13:00', '16:00', 'Auditorium 1', '200', 'final', 'STF-004', 'STF-005', '']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function verifyPass(Request $request, $token)
    {
        $verificationResult = $this->examService->verifyStudentPass($token);

        if (! $verificationResult) {
            return back()->with('warning', "Unrecognized student verification pass token: {$token}");
        }

        return back()->with('verified_candidate', $verificationResult);
    }

    public function markAttendance(Request $request)
    {
        $validated = $request->validate([
            'exam_schedule_id' => 'required|exists:exam_schedules,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'nullable|string|in:present,late,flagged',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $attendance = $this->examService->recordAttendance(
                $validated['exam_schedule_id'],
                $validated['student_id'],
                $validated['status'] ?? 'present',
                $validated['notes'] ?? null
            );

            $student = Student::with('user')->find($validated['student_id']);
            $schedule = ExamSchedule::with('course')->find($validated['exam_schedule_id']);

            return back()->with('success', "Candidate Verified! {$student->user?->name} marked PRESENT for {$schedule->course?->code}.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', "Verification Blocked: " . $e->getMessage());
        }
    }
}
