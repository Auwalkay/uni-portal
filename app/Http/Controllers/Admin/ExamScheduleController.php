<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Exam;
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

    public function create(Request $request)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can create exam schedules.');
        }

        $sessions = AcademicCacheService::getSessions();
        $semesters = AcademicCacheService::getSemesters();
        $departments = AcademicCacheService::getAcademicDepartments();
        $courses = AcademicCacheService::getAllCourses();
        $buildings = AcademicCacheService::getExamBuildings();
        $exams = Exam::with(['session', 'semester'])->orderBy('created_at', 'desc')->get();

        $selectedExamId = $request->query('exam_id');
        $selectedExam = $selectedExamId ? Exam::find($selectedExamId) : null;

        $currentSessionId = $selectedExam?->session_id ?? AcademicCacheService::getCurrentSession()?->id ?? $sessions->first()?->id ?? '';
        $currentSemesterId = $selectedExam?->semester_id ?? AcademicCacheService::getCurrentSemester()?->id ?? $semesters->first()?->id ?? '';

        return Inertia::render('Admin/Exams/Form', [
            'sessions' => $sessions,
            'semesters' => $semesters,
            'departments' => $departments,
            'courses' => $courses,
            'buildings' => $buildings,
            'exams' => $exams,
            'preselectedExamId' => $selectedExamId ?? '',
            'currentSessionId' => $currentSessionId,
            'currentSemesterId' => $currentSemesterId,
            'exam' => null,
        ]);
    }

    public function edit(ExamSchedule $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can edit exam schedules.');
        }

        $exam->load(['course', 'department', 'session', 'semester', 'exam']);

        $sessions = AcademicCacheService::getSessions();
        $semesters = AcademicCacheService::getSemesters();
        $departments = AcademicCacheService::getAcademicDepartments();
        $courses = AcademicCacheService::getAllCourses();
        $buildings = AcademicCacheService::getExamBuildings();
        $exams = Exam::with(['session', 'semester'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Exams/Form', [
            'sessions' => $sessions,
            'semesters' => $semesters,
            'departments' => $departments,
            'courses' => $courses,
            'buildings' => $buildings,
            'exams' => $exams,
            'currentSessionId' => $exam->session_id,
            'currentSemesterId' => $exam->semester_id,
            'exam' => $exam,
        ]);
    }

    protected function canManageExams(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole(['admin', 'super_admin', 'exams_officer', 'academic_admin']) ||
               $user->can('manage_exams') ||
               $user->can('view_exams') ||
               $user->can('create_exams') ||
               $user->can('edit_exams') ||
               $user->can('publish_exams') ||
               $user->can('assign_invigilators');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $canManageExams = $this->canManageExams();
        $staff = Staff::where('user_id', $user?->id)->first();

        $currentSession = AcademicCacheService::getCurrentSession();
        $currentSemester = AcademicCacheService::getCurrentSemester();
        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedSemesterId = $request->input('semester_id', $currentSemester?->id);
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
            ->when(! $canManageExams, function ($q) use ($staff) {
                // Normal staff should ONLY see courses they are invigilating
                $q->whereHas('invigilators', fn ($iq) => $iq->where('staff_id', $staff?->id ?? '00000000-0000-0000-0000-000000000000'));
            })
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
            ->paginate(15)
            ->withQueryString();

        // Delegate conflict detection to ExamManagementService
        $conflicts = $canManageExams ? $this->examService->detectVenueConflicts($selectedSessionId, $selectedSemesterId) : [];

        // Calculate KPI metrics scoped appropriately in a single aggregated query
        $baseMetricsQuery = ExamSchedule::when(! $canManageExams, fn ($q) => $q->whereHas('invigilators', fn ($iq) => $iq->where('staff_id', $staff?->id ?? '00000000-0000-0000-0000-000000000000')))
            ->when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))
            ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId));

        $metricsData = (clone $baseMetricsQuery)
            ->selectRaw('COUNT(*) as total_exams, COALESCE(SUM(max_capacity), 0) as total_capacity')
            ->first();

        $totalExams = (int) ($metricsData->total_exams ?? 0);
        $totalCapacity = (int) ($metricsData->total_capacity ?? 0);

        $totalInvigilators = ExamInvigilator::whereHas('schedule', fn ($q) => 
            $q->when($selectedSessionId, fn ($sq) => $sq->where('session_id', $selectedSessionId))
              ->when($selectedSemesterId, fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
        )
        ->when(! $canManageExams, fn ($q) => $q->where('staff_id', $staff?->id))
        ->count();

        $incidentsQuery = ExamIncident::with([
                'schedule.course',
                'student.user',
                'invigilator.user',
                'logger',
            ])
            ->whereHas('schedule', fn ($q) => 
                $q->when($selectedSessionId, fn ($sq) => $sq->where('session_id', $selectedSessionId))
                  ->when($selectedSemesterId, fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
            )
            ->when(! $canManageExams, function ($q) use ($staff) {
                $q->where(function ($iq) use ($staff) {
                    $iq->where('invigilator_id', $staff?->id)
                      ->orWhere('logged_by', auth()->id());
                });
            })
            ->latest('created_at');

        $totalIncidents = (clone $incidentsQuery)->count();
        $allIncidents = $incidentsQuery->get();

        $examsList = Exam::with(['session', 'semester'])
            ->withCount('schedules')
            ->when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))
            ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
            ->latest()
            ->get();

        $filteredSemesters = Semester::when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Admin/Exams/Index', [
            'schedules' => $schedules,
            'exams' => $examsList,
            'sessions' => AcademicCacheService::getSessions() ?? Session::orderBy('name', 'desc')->get(),
            'semesters' => $filteredSemesters->isNotEmpty() ? $filteredSemesters : (AcademicCacheService::getSemesters() ?? Semester::orderBy('name', 'asc')->get()),
            'departments' => AcademicCacheService::getAcademicDepartments() ?? Department::orderBy('name', 'asc')->get(),
            'courses' => AcademicCacheService::getAllCourses(),
            'staff' => AcademicCacheService::getStaffList(),
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
            'allIncidents' => $allIncidents,
            'conflicts' => $conflicts,
            'buildings' => AcademicCacheService::getExamBuildings(),
            'filters' => [
                'session_id' => $selectedSessionId,
                'semester_id' => $selectedSemesterId,
                'department_id' => $selectedDepartmentId,
                'level' => $selectedLevel,
                'exam_type' => $examType,
                'search' => $search,
            ],
            'isPublished' => filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN),
            'canManageExams' => $canManageExams,
        ]);
    }

    public function showExam(Exam $exam, Request $request)
    {
        $user = auth()->user();
        $canManageExams = $this->canManageExams();
        $staff = Staff::where('user_id', $user?->id)->first();

        $exam->load(['session', 'semester']);

        $schedulesQuery = ExamSchedule::with([
                'course',
                'department',
                'session',
                'semester',
                'invigilators.staff.user',
                'incidents.student.user',
                'incidents.invigilator.user',
            ])
            ->withCount(['attendances'])
            ->where(function ($q) use ($exam) {
                $q->where('exam_id', $exam->id)
                  ->orWhere(function ($sq) use ($exam) {
                      $sq->whereNull('exam_id')
                        ->where('session_id', $exam->session_id)
                        ->where('semester_id', $exam->semester_id);
                  });
            })
            ->when(! $canManageExams, function ($q) use ($staff) {
                $q->whereHas('invigilators', fn ($iq) => $iq->where('staff_id', $staff?->id ?? '00000000-0000-0000-0000-000000000000'));
            });

        $schedules = (clone $schedulesQuery)->orderBy('exam_date', 'asc')->orderBy('start_time', 'asc')->get();

        $incidents = ExamIncident::with(['schedule.course', 'student.user', 'invigilator.user', 'logger'])
            ->whereHas('schedule', function ($q) use ($exam) {
                $q->where('exam_id', $exam->id)
                  ->orWhere(function ($sq) use ($exam) {
                      $sq->whereNull('exam_id')
                        ->where('session_id', $exam->session_id)
                        ->where('semester_id', $exam->semester_id);
                  });
            })
            ->latest()
            ->get();

        $conflicts = $canManageExams ? $this->examService->detectVenueConflicts($exam->session_id, $exam->semester_id) : [];

        return Inertia::render('Admin/Exams/Show', [
            'exam' => $exam,
            'schedules' => $schedules,
            'incidents' => $incidents,
            'conflicts' => $conflicts,
            'sessions' => AcademicCacheService::getSessions(),
            'semesters' => AcademicCacheService::getSemesters(),
            'departments' => AcademicCacheService::getAcademicDepartments(),
            'courses' => AcademicCacheService::getAllCourses(),
            'staff' => AcademicCacheService::getStaffList(),
            'students' => Student::with('user:id,name')->select('id', 'user_id', 'matriculation_number')->limit(100)->get()->map(fn ($st) => [
                'id' => $st->id,
                'name' => $st->user?->name ?? 'Student',
                'matric_number' => $st->matric_number ?? 'N/A',
            ]),
            'buildings' => AcademicCacheService::getExamBuildings(),
            'canManageExams' => $canManageExams,
        ]);
    }

    public function storeExam(Request $request)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can create examination exercises.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'exam_type' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'instructions' => 'nullable|string',
        ]);

        $validated['reference_id'] = 'EXM-' . strtoupper(substr(uniqid(), -6));
        $validated['created_by'] = auth()->id();
        $validated['is_published'] = false;
        $validated['docket_printing_enabled'] = true;

        Exam::create($validated);

        return back()->with('success', 'Examination exercise created successfully.');
    }

    public function updateExam(Request $request, Exam $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized to update examination exercise.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'exam_type' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'instructions' => 'nullable|string',
        ]);

        $exam->update($validated);

        return back()->with('success', 'Examination exercise updated successfully.');
    }

    public function destroyExam(Exam $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized to delete examination exercise.');
        }

        $exam->delete();

        return back()->with('success', 'Examination exercise deleted successfully.');
    }

    public function togglePublishExam(Exam $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized to change publication status.');
        }

        $exam->update(['is_published' => ! $exam->is_published]);

        $msg = $exam->is_published ? 'Examination exercise published.' : 'Examination exercise un-published.';
        return back()->with('success', $msg);
    }

    public function toggleDocketExam(Exam $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized to change docket printing status.');
        }

        $exam->update(['docket_printing_enabled' => ! $exam->docket_printing_enabled]);

        $msg = $exam->docket_printing_enabled ? 'Docket printing enabled.' : 'Docket printing disabled.';
        return back()->with('success', $msg);
    }

    public function scanner(Request $request)
    {
        $canManageExams = $this->canManageExams();
        $staff = Staff::where('user_id', auth()->id())->first();

        $schedules = ExamSchedule::with([
                'course',
                'department',
                'session',
                'semester',
                'invigilators.staff.user',
            ])
            ->withCount(['attendances'])
            ->when(! $canManageExams, function ($q) use ($staff) {
                $q->whereHas('invigilators', fn ($iq) => $iq->where('staff_id', $staff?->id ?? '00000000-0000-0000-0000-000000000000'));
            })
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $activeScheduleId = $request->input('schedule_id', $schedules->first()?->id);

        return Inertia::render('Admin/Exams/Scanner', [
            'schedules' => $schedules,
            'activeScheduleId' => $activeScheduleId,
            'canManageExams' => $canManageExams,
        ]);
    }

    public function togglePublish()
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can manage exam timetables.');
        }

        $current = filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN);
        $new = !$current;
        \App\Models\SystemSetting::set('publish_exam_timetable', $new ? '1' : '0');
        AcademicCacheService::clearAll();

        $statusStr = $new ? 'published to students' : 'hidden from students (draft mode)';
        return back()->with('success', "Exam timetable is now {$statusStr}.");
    }

    public function store(Request $request)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can create exam schedules.');
        }

        $validated = $request->validate([
            'exam_id' => 'nullable|exists:exams,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:500',
            'exam_type' => 'nullable|string|in:final,mid_term,cbt,resit',
            'max_capacity' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
        ]);

        $courseIds = [];
        if (!empty($validated['course_ids'])) {
            $courseIds = array_unique(array_filter($validated['course_ids']));
        } elseif (!empty($validated['course_id'])) {
            $courseIds = [$validated['course_id']];
        }

        if (empty($courseIds)) {
            return back()->withErrors(['course_id' => 'Please select at least one course.']);
        }

        $validated['exam_type'] = $validated['exam_type'] ?? 'final';
        $venues = array_filter(array_map('trim', explode(',', $validated['venue'])));

        $createdCount = 0;
        foreach ($courseIds as $cId) {
            foreach ($venues as $v) {
                $item = $validated;
                unset($item['course_ids']);
                $item['course_id'] = $cId;
                $item['venue'] = $v;
                $item['reference_id'] = 'EXM-' . strtoupper(substr(uniqid(), -6));
                $item['created_by'] = auth()->id();
                ExamSchedule::create($item);
                $createdCount++;
            }
        }

        if ($createdCount > 1) {
            return redirect()->route('admin.exams.index')->with('success', "Successfully scheduled {$createdCount} exam timetables for selected courses & venues.");
        }

        return redirect()->route('admin.exams.index')->with('success', 'Exam schedule created successfully.');
    }

    public function update(Request $request, ExamSchedule $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can update exam schedules.');
        }

        $validated = $request->validate([
            'exam_id' => 'nullable|exists:exams,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'exam_type' => 'nullable|string|in:final,mid_term,cbt,resit',
            'max_capacity' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
        ]);

        $validated['exam_type'] = $validated['exam_type'] ?? 'final';

        $exam->update($validated);

        return redirect()->route('admin.exams.index')->with('success', 'Exam schedule updated successfully.');
    }

    public function destroy(ExamSchedule $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can delete exam schedules.');
        }

        $exam->delete();

        return back()->with('success', 'Exam schedule deleted successfully.');
    }

    public function assignInvigilator(Request $request, ExamSchedule $exam)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can assign invigilators.');
        }

        $validated = $request->validate([
            'staff_id' => 'nullable|exists:staff,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff,id',
            'role' => 'required|string|in:chief,assistant',
        ]);

        $staffIds = [];
        if (!empty($validated['staff_ids'])) {
            $staffIds = array_unique(array_filter($validated['staff_ids']));
        } elseif (!empty($validated['staff_id'])) {
            $staffIds = [$validated['staff_id']];
        }

        if (empty($staffIds)) {
            return back()->withErrors(['staff_id' => 'Please select at least one staff member.']);
        }

        $assignedCount = 0;
        foreach ($staffIds as $sId) {
            ExamInvigilator::updateOrCreate(
                [
                    'exam_schedule_id' => $exam->id,
                    'staff_id' => $sId,
                ],
                [
                    'role' => $validated['role'],
                    'status' => 'assigned',
                ]
            );
            $assignedCount++;
        }

        return back()->with('success', "Successfully assigned {$assignedCount} invigilator(s).");
    }

    public function removeInvigilator(ExamInvigilator $invigilator)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can remove invigilators.');
        }

        $invigilator->delete();

        return back()->with('success', 'Invigilator removed successfully.');
    }

    public function logIncident(Request $request, ExamSchedule $exam)
    {
        // Invigilators assigned to this exam OR Exams Office can log incidents
        $staff = Staff::where('user_id', auth()->id())->first();
        $isInvigilator = $staff ? ExamInvigilator::where('exam_schedule_id', $exam->id)->where('staff_id', $staff->id)->exists() : false;

        if (! $this->canManageExams() && ! $isInvigilator) {
            abort(403, 'Unauthorized: You can only log incidents for exams assigned to you.');
        }

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

    public function storeIncident(Request $request)
    {
        if (! $this->canManageExams() && ! auth()->user()->can('log_exam_incidents')) {
            abort(403, 'Unauthorized: You do not have permission to log exam incidents.');
        }

        $validated = $request->validate([
            'exam_schedule_id' => 'required|exists:exam_schedules,id',
            'student_id' => 'required|exists:students,id',
            'invigilator_id' => 'nullable|exists:staff,id',
            'incident_type' => 'required|string|in:malpractice,contraband,medical,impersonation,absenteeism,other',
            'description' => 'required|string',
            'status' => 'required|string|in:logged,under_investigation,resolved,sanctioned',
            'action_taken' => 'nullable|string',
        ]);

        $validated['reference_id'] = 'INC-' . strtoupper(substr(uniqid(), -6));
        $validated['logged_by'] = auth()->id();

        ExamIncident::create($validated);

        return back()->with('success', 'Exam incident registered successfully.');
    }

    public function updateIncident(Request $request, ExamIncident $incident)
    {
        if (! $this->canManageExams() && ! auth()->user()->can('log_exam_incidents')) {
            abort(403, 'Unauthorized to update incident records.');
        }

        $validated = $request->validate([
            'status' => 'required|string|in:logged,under_investigation,resolved,sanctioned',
            'action_taken' => 'nullable|string',
        ]);

        $incident->update($validated);

        return back()->with('success', 'Exam incident record updated successfully.');
    }

    public function incidentsIndex(Request $request)
    {
        $user = auth()->user();
        $canManageExams = $this->canManageExams();
        $staff = Staff::where('user_id', $user?->id)->first();

        $currentSession = AcademicCacheService::getCurrentSession();
        $currentSemester = AcademicCacheService::getCurrentSemester();
        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedSemesterId = $request->input('semester_id', $currentSemester?->id);
        $selectedIncidentType = $request->input('incident_type');
        $selectedStatus = $request->input('status');
        $search = $request->input('search');

        $query = ExamIncident::with([
                'schedule.course',
                'schedule.session',
                'schedule.semester',
                'student.user',
                'invigilator.user',
                'logger',
            ])
            ->whereHas('schedule', fn ($q) => 
                $q->when($selectedSessionId, fn ($sq) => $sq->where('session_id', $selectedSessionId))
                  ->when($selectedSemesterId, fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
            )
            ->when(! $canManageExams, function ($q) use ($staff) {
                $q->where(function ($iq) use ($staff) {
                    $iq->where('invigilator_id', $staff?->id)
                      ->orWhere('logged_by', auth()->id());
                });
            })
            ->when($selectedIncidentType, fn ($q) => $q->where('incident_type', $selectedIncidentType))
            ->when($selectedStatus, fn ($q) => $q->where('status', $selectedStatus))
            ->when($search, function ($q, $s) {
                $q->where('reference_id', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhereHas('student.user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('student', fn ($sq) => $sq->where('matriculation_number', 'like', "%{$s}%"))
                  ->orWhereHas('schedule.course', fn ($cq) => $cq->where('code', 'like', "%{$s}%")->orWhere('title', 'like', "%{$s}%"));
            });

        $incidents = $query->latest('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'logged' => ExamIncident::where('status', 'logged')->count(),
            'under_investigation' => ExamIncident::where('status', 'under_investigation')->count(),
            'resolved' => ExamIncident::where('status', 'resolved')->count(),
            'sanctioned' => ExamIncident::where('status', 'sanctioned')->count(),
        ];

        $examOptions = ExamSchedule::with('course')
            ->when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))
            ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
            ->latest('exam_date')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'label' => ($e->course?->code ?? 'Exam') . ' - ' . ($e->course?->title ?? '') . ($e->exam_date ? " ({$e->exam_date})" : ''),
            ])
            ->values();

        if ($examOptions->isEmpty()) {
            $examOptions = ExamSchedule::with('course')
                ->latest('exam_date')
                ->take(100)
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'label' => ($e->course?->code ?? 'Exam') . ' - ' . ($e->course?->title ?? '') . ($e->exam_date ? " ({$e->exam_date})" : ''),
                ])
                ->values();
        }

        $studentOptions = Student::with('user:id,name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => (($s->matriculation_number || $s->matric_number) ? ($s->matriculation_number ?? $s->matric_number) . ' - ' : '') . ($s->user?->name ?? 'Student'),
            ])
            ->values();

        $staffOptions = Staff::with('user:id,name')
            ->get()
            ->map(fn ($st) => [
                'id' => $st->id,
                'label' => ($st->user?->name ?? 'Staff') . ($st->staff_number ? " ({$st->staff_number})" : ''),
            ])
            ->values();

        return Inertia::render('Admin/Exams/Incidents', [
            'incidents' => $incidents,
            'sessions' => AcademicCacheService::getSessions() ?? Session::orderBy('name', 'desc')->get(),
            'semesters' => AcademicCacheService::getSemesters() ?? Semester::orderBy('name', 'asc')->get(),
            'departments' => AcademicCacheService::getAcademicDepartments() ?? Department::orderBy('name', 'asc')->get(),
            'examOptions' => $examOptions,
            'studentOptions' => $studentOptions,
            'staffOptions' => $staffOptions,
            'stats' => $stats,
            'filters' => [
                'session_id' => $selectedSessionId,
                'semester_id' => $selectedSemesterId,
                'incident_type' => $selectedIncidentType,
                'status' => $selectedStatus,
                'search' => $search,
            ],
            'canManageExams' => $canManageExams,
        ]);
    }

    public function import(Request $request)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can import exam timetables.');
        }

        $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'file' => 'required|file|mimes:csv,xls,xlsx',
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

    public function downloadTemplate(Request $request)
    {
        if (! $this->canManageExams()) {
            abort(403, 'Unauthorized: Only Exams Office can download exam templates.');
        }

        $type = $request->input('type', 'combined');

        if ($type === 'invigilators_only') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="exam_invigilators_assignment_template.csv"',
            ];

            $columns = [
                'course_code',
                'venue',
                'staff_number',
                'role',
            ];

            $callback = function () use ($columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
                fputcsv($file, ['CSC101', 'Multipurpose Hall A', 'STF-001', 'chief']);
                fputcsv($file, ['CSC101', 'Multipurpose Hall A', 'STF-002', 'assistant']);
                fputcsv($file, ['MTH101', 'Auditorium 1', 'STF-003', 'chief']);
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

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

        if (! $this->canManageExams()) {
            $staff = Staff::where('user_id', auth()->id())->first();
            $isInvigilator = $staff ? ExamInvigilator::where('exam_schedule_id', $validated['exam_schedule_id'])
                ->where('staff_id', $staff->id)
                ->exists() : false;

            if (! $isInvigilator) {
                return back()->with('error', 'Access Denied: You are not assigned to invigilate this exam paper.');
            }
        }

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
