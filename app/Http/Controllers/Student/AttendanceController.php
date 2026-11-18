<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseLectureSession;
use App\Models\CourseRegistration;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentCourseAttendance;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)
            ->with(['department', 'program'])
            ->first();

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        // 1. Determine student stream (PG or UG)
        $isPg = $student->isPostgraduate();
        $stream = $isPg ? 'PG' : 'UG';

        // 2. Resolve default active session for this student type (PG or UG) via cache
        $calendar = $student->program?->calendar_type ?: 'semester';
        $defaultSession = \App\Services\AcademicCacheService::getCurrentSessionForStream($stream, $calendar)
            ?? \App\Services\AcademicCacheService::getCurrentSession();

        // 3. Fetch cached academic sessions scoped to the student's stream
        $sessions = \App\Services\AcademicCacheService::getSessionsForStream($stream);
        if ($sessions->isEmpty()) {
            $sessions = \App\Services\AcademicCacheService::getSessions();
        }
        if (! $defaultSession) {
            $defaultSession = $sessions->first();
        }

        // 4. Resolve selected session
        $sessionId = $request->input('session_id');
        if (!$sessionId || !$sessions->contains('id', $sessionId)) {
            $sessionId = $defaultSession?->id;
        }
        $selectedSession = $sessionId ? $sessions->firstWhere('id', $sessionId) : $defaultSession;

        if (!$selectedSession) {
            return Inertia::render('Student/Attendance/Index', [
                'courses' => [],
                'stats' => [
                    'total_courses' => 0,
                    'total_classes' => 0,
                    'total_attended' => 0,
                    'overall_percentage' => null,
                ],
                'sessions' => [],
                'semesters' => [],
                'filters' => [
                    'session_id' => null,
                    'semester_id' => null,
                ],
                'session' => null,
                'selectedSession' => null,
                'selectedSemester' => null,
                'studentStream' => $stream,
                'minRequiredPercentage' => 75,
                'isEnforced' => false,
            ]);
        }

        // 5. Fetch semesters for selected session
        $semesters = Semester::where('session_id', $selectedSession->id)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'is_current']);

        // 6. Resolve default active semester for this session
        $defaultSemester = $semesters->firstWhere('is_current', true) ?? $semesters->first();

        // 7. Resolve selected semester (default to active semester when omitted)
        if ($request->has('semester_id')) {
            $semesterId = $request->input('semester_id');
            if ($semesterId !== 'all' && !$semesters->contains('id', $semesterId)) {
                $semesterId = $defaultSemester?->id;
            }
        } else {
            $semesterId = $defaultSemester?->id;
        }
        $selectedSemester = $semesterId && $semesterId !== 'all' ? $semesters->firstWhere('id', $semesterId) : null;

        // 8. Fetch registered courses for the selected session and semester
        $registrationsQuery = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $selectedSession->id);

        if ($semesterId && $semesterId !== 'all') {
            $registrationsQuery->where('semester_id', $semesterId);
        }

        $registrations = $registrationsQuery
            ->with(['course:id,code,title,units', 'semester:id,name'])
            ->get();

        $courseIds = $registrations->pluck('course_id');
        $regIds = $registrations->pluck('id');

        // 9. Batch count of applicable lectures held per course in this session (department-aware)
        $coursesWithSessions = CourseLectureSession::where('session_id', $selectedSession->id)
            ->whereIn('course_id', $courseIds)
            ->select('course_id')
            ->distinct()
            ->pluck('course_id')
            ->flip();

        $studentDeptId = $student->department_id;

        $applicableSessionsQuery = CourseLectureSession::where('session_id', $selectedSession->id)
            ->whereIn('course_id', $courseIds);

        if ($studentDeptId) {
            $applicableSessionsQuery->where(function ($q) use ($studentDeptId) {
                $q->whereNull('department_ids')
                    ->orWhere('department_ids', '[]')
                    ->orWhereJsonContains('department_ids', (string) $studentDeptId);
            });
        }

        $applicableLectureCounts = $applicableSessionsQuery
            ->select('course_id')
            ->selectRaw('count(distinct lecture_date) as total_held')
            ->groupBy('course_id')
            ->pluck('total_held', 'course_id');

        // Only query fallback for legacy courses that do NOT have any CourseLectureSession records
        $coursesNeedingFallback = $courseIds->diff($coursesWithSessions->keys());
        $fallbackTotalHeld = collect();
        if ($coursesNeedingFallback->isNotEmpty()) {
            $fallbackTotalHeld = StudentCourseAttendance::where('session_id', $selectedSession->id)
                ->whereIn('course_id', $coursesNeedingFallback)
                ->select('course_id')
                ->selectRaw('count(distinct attendance_date) as total_held')
                ->groupBy('course_id')
                ->pluck('total_held', 'course_id');
        }

        // 10. Single indexed query to fetch student's date-by-date attendance logs for each course
        $logsByCourse = StudentCourseAttendance::where('session_id', $selectedSession->id)
            ->where('student_id', $student->id)
            ->whereIn('course_id', $courseIds)
            ->when($regIds->isNotEmpty(), fn ($q) => $q->whereIn('course_registration_id', $regIds))
            ->select('course_id', 'attendance_date', 'status')
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('course_id');

        // 11. High-performance in-memory calculation of student attended count (zero redundant DB queries)
        $studentAttended = $logsByCourse->map(function ($logs) {
            return $logs->filter(function ($item) {
                $val = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status;
                return strtolower($val) === 'present';
            })->pluck('attendance_date')->map(function ($d) {
                return $d instanceof \Carbon\CarbonInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
            })->unique()->count();
        });

        $totalClassesSum = 0;
        $totalAttendedSum = 0;

        $coursesData = $registrations->map(function ($reg) use ($applicableLectureCounts, $fallbackTotalHeld, $coursesWithSessions, $studentAttended, $logsByCourse, &$totalClassesSum, &$totalAttendedSum) {
            $hasSessionRecords = isset($coursesWithSessions[$reg->course_id]);
            $totalHeld = $hasSessionRecords
                ? (int) ($applicableLectureCounts->get($reg->course_id) ?? 0)
                : (int) ($fallbackTotalHeld->get($reg->course_id) ?? 0);

            $attended = (int) ($studentAttended->get($reg->course_id) ?? 0);

            // Defensive: student attended cannot exceed total held
            if ($attended > $totalHeld) {
                $totalHeld = $attended;
            }

            $missed = max(0, $totalHeld - $attended);
            $percentage = $totalHeld > 0 ? round(($attended / $totalHeld) * 100, 1) : null;

            $totalClassesSum += $totalHeld;
            $totalAttendedSum += $attended;

            $dates = ($logsByCourse->get($reg->course_id) ?? collect())->map(fn ($log) => [
                'date' => $log->attendance_date->format('Y-m-d'),
                'formatted_date' => $log->attendance_date->format('M d, Y'),
                'status' => $log->status->value ?? (string) $log->status,
            ])->values();

            return [
                'registration_id' => $reg->id,
                'course_id' => $reg->course_id,
                'course_code' => $reg->course?->code ?? 'N/A',
                'course_title' => $reg->course?->title ?? 'N/A',
                'units' => $reg->course?->units ?? 0,
                'semester_id' => $reg->semester_id,
                'semester_name' => $reg->semester?->name ?? 'N/A',
                'total_classes' => $totalHeld,
                'attended_classes' => $attended,
                'missed_classes' => $missed,
                'attendance_percentage' => $percentage,
                'dates' => $dates,
            ];
        });

        $overallPercentage = $totalClassesSum > 0 ? round(($totalAttendedSum / $totalClassesSum) * 100, 1) : null;
        $minRequired = (int) SystemSetting::get('attendance_exam_docket_min_percentage', 75);
        $enforceAttendance = filter_var(SystemSetting::get('enforce_attendance_for_exam_docket', false), FILTER_VALIDATE_BOOLEAN);

        return Inertia::render('Student/Attendance/Index', [
            'courses' => $coursesData,
            'stats' => [
                'total_courses' => $registrations->count(),
                'total_classes' => $totalClassesSum,
                'total_attended' => $totalAttendedSum,
                'overall_percentage' => $overallPercentage,
            ],
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'academic_stream' => $s->academic_stream,
                'is_current' => (bool) $s->is_current,
            ]),
            'semesters' => $semesters->map(fn ($sem) => [
                'id' => $sem->id,
                'name' => $sem->name,
                'is_current' => (bool) $sem->is_current,
            ]),
            'filters' => [
                'session_id' => $sessionId,
                'semester_id' => $semesterId,
            ],
            'session' => [
                'id' => $selectedSession->id,
                'name' => $selectedSession->name,
                'academic_stream' => $selectedSession->academic_stream,
            ],
            'selectedSession' => [
                'id' => $selectedSession->id,
                'name' => $selectedSession->name,
                'academic_stream' => $selectedSession->academic_stream,
            ],
            'selectedSemester' => $selectedSemester ? [
                'id' => $selectedSemester->id,
                'name' => $selectedSemester->name,
                'is_current' => (bool) $selectedSemester->is_current,
            ] : null,
            'studentStream' => $stream,
            'minRequiredPercentage' => $minRequired,
            'isEnforced' => $enforceAttendance,
        ]);
    }
}
