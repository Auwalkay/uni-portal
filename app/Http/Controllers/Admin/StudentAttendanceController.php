<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLectureSession;
use App\Models\CourseRegistration;
use App\Models\StudentCourseAttendance;
use App\Services\AcademicCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperOrAdmin = $user->hasRole(['admin', 'super_admin']) || $user->can('manage_courses');
        $isDean = ! $isSuperOrAdmin && ($user->hasRole('dean') || $user->can('view_faculty_courses'));
        $isHod = ! $isSuperOrAdmin && ! $isDean && ($user->hasRole('hod') || $user->can('view_department_courses') || $user->can('manage_department_courses'));

        if (! $isSuperOrAdmin && ! $isDean && ! $isHod && ! $user->can('view_results')) {
            abort(403, 'Unauthorized to view university-wide student attendance.');
        }

        $staff = $user->staff?->loadMissing('department');
        $facultyId = $isDean ? $staff?->department?->faculty_id : null;
        $hodDepartmentId = $isHod ? $staff?->department_id : null;

        $defaultSession = AcademicCacheService::getCurrentSessionForStream('UG', 'semester')
            ?? AcademicCacheService::getCurrentSession();
        $sessionId = $request->input('session_id', $defaultSession?->id);
        $departmentId = $hodDepartmentId ?: $request->input('department_id');
        $threshold = $request->input('threshold', 'all'); // all | eligible | below
        $search = trim((string) $request->input('search', ''));

        // Subquery: lecture sessions held per course in this session
        $heldSub = DB::table('course_lecture_sessions')
            ->select('course_id', DB::raw('COUNT(*) as held_count'))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->groupBy('course_id');

        // Subquery: attended ('present') count per student & course in this session
        $attendedSub = DB::table('student_course_attendances')
            ->select('student_id', 'course_id', DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as attended_count"))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->groupBy('student_id', 'course_id');

        $baseQuery = CourseRegistration::query()
            ->join('students', 'course_registrations.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
            ->leftJoin('departments', 'students.department_id', '=', 'departments.id')
            ->leftJoinSub($heldSub, 'held_sessions', function ($join) {
                $join->on('course_registrations.course_id', '=', 'held_sessions.course_id');
            })
            ->leftJoinSub($attendedSub, 'student_att', function ($join) {
                $join->on('course_registrations.student_id', '=', 'student_att.student_id')
                    ->on('course_registrations.course_id', '=', 'student_att.course_id');
            })
            ->when($sessionId, fn ($q) => $q->where('course_registrations.session_id', $sessionId))
            ->when($facultyId, fn ($q) => $q->where('students.faculty_id', $facultyId))
            ->when($departmentId && $departmentId !== 'all', fn ($q) => $q->where('students.department_id', $departmentId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('students.matriculation_number', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('courses.code', 'like', "%{$search}%")
                        ->orWhere('courses.title', 'like', "%{$search}%");
                });
            });

        if ($threshold === 'eligible') {
            $baseQuery->whereRaw('COALESCE(held_sessions.held_count, 0) > 0')
                ->whereRaw('(COALESCE(student_att.attended_count, 0) * 100.0 / held_sessions.held_count) >= 75');
        } elseif ($threshold === 'below') {
            $baseQuery->whereRaw('COALESCE(held_sessions.held_count, 0) > 0')
                ->whereRaw('(COALESCE(student_att.attended_count, 0) * 100.0 / held_sessions.held_count) < 75');
        }

        $records = (clone $baseQuery)
            ->select([
                'course_registrations.id',
                'course_registrations.student_id',
                'course_registrations.course_id',
                'students.matriculation_number',
                'users.name as student_name',
                'courses.code as course_code',
                'courses.title as course_title',
                'departments.name as department_name',
                DB::raw('COALESCE(held_sessions.held_count, 0) as sessions_held'),
                DB::raw('COALESCE(student_att.attended_count, 0) as sessions_attended'),
            ])
            ->orderBy('courses.code')
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($row) {
                $held = (int) $row->sessions_held;
                $attended = (int) $row->sessions_attended;
                $pct = $held > 0 ? round(($attended / $held) * 100, 1) : null;

                return [
                    'id' => $row->id,
                    'student_id' => $row->student_id,
                    'course_id' => $row->course_id,
                    'student_name' => $row->student_name,
                    'matriculation_number' => $row->matriculation_number,
                    'course_code' => $row->course_code,
                    'course_title' => $row->course_title,
                    'department_name' => $row->department_name ?? 'Unassigned',
                    'sessions_held' => $held,
                    'sessions_attended' => $attended,
                    'percentage' => $pct,
                ];
            });

        $statsCacheKey = "admin_student_att_stats_{$sessionId}_{$facultyId}_{$departmentId}";
        $stats = Cache::remember($statsCacheKey, 300, function () use ($sessionId, $facultyId, $departmentId, $heldSub, $attendedSub) {
            $totalHeld = CourseLectureSession::when($sessionId, fn ($q) => $q->where('session_id', $sessionId))->count();

            $kpiBase = CourseRegistration::query()
                ->join('students', 'course_registrations.student_id', '=', 'students.id')
                ->leftJoinSub($heldSub, 'held_sessions', fn ($j) => $j->on('course_registrations.course_id', '=', 'held_sessions.course_id'))
                ->leftJoinSub($attendedSub, 'student_att', function ($j) {
                    $j->on('course_registrations.student_id', '=', 'student_att.student_id')
                        ->on('course_registrations.course_id', '=', 'student_att.course_id');
                })
                ->when($sessionId, fn ($q) => $q->where('course_registrations.session_id', $sessionId))
                ->when($facultyId, fn ($q) => $q->where('students.faculty_id', $facultyId))
                ->when($departmentId && $departmentId !== 'all', fn ($q) => $q->where('students.department_id', $departmentId));

            $totalRecords = (clone $kpiBase)->count();
            $belowThreshold = (clone $kpiBase)
                ->whereRaw('COALESCE(held_sessions.held_count, 0) > 0')
                ->whereRaw('(COALESCE(student_att.attended_count, 0) * 100.0 / held_sessions.held_count) < 75')
                ->count();

            return [
                'total_sessions_held' => $totalHeld,
                'total_records' => $totalRecords,
                'below_threshold_count' => $belowThreshold,
            ];
        });

        $departments = AcademicCacheService::getAcademicDepartments()
            ->when($facultyId, fn ($c) => $c->where('faculty_id', $facultyId))
            ->when($hodDepartmentId, fn ($c) => $c->where('id', $hodDepartmentId))
            ->values()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]);

        return Inertia::render('Admin/Academic/StudentAttendance', [
            'records' => $records,
            'stats' => $stats,
            'sessions' => AcademicCacheService::getSessions(),
            'departments' => $departments,
            'filters' => [
                'session_id' => $sessionId,
                'department_id' => $departmentId ?: 'all',
                'threshold' => $threshold,
                'search' => $search,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $isSuperOrAdmin = $user->hasRole(['admin', 'super_admin']) || $user->can('manage_courses');
        $isDean = ! $isSuperOrAdmin && ($user->hasRole('dean') || $user->can('view_faculty_courses'));
        $isHod = ! $isSuperOrAdmin && ! $isDean && ($user->hasRole('hod') || $user->can('view_department_courses') || $user->can('manage_department_courses'));

        if (! $isSuperOrAdmin && ! $isDean && ! $isHod && ! $user->can('view_results')) {
            abort(403, 'Unauthorized.');
        }

        $staff = $user->staff?->loadMissing('department');
        $facultyId = $isDean ? $staff?->department?->faculty_id : null;
        $hodDepartmentId = $isHod ? $staff?->department_id : null;

        $sessionId = $request->input('session_id');
        $departmentId = $hodDepartmentId ?: $request->input('department_id');
        $threshold = $request->input('threshold', 'all');
        $search = trim((string) $request->input('search', ''));

        $heldSub = DB::table('course_lecture_sessions')
            ->select('course_id', DB::raw('COUNT(*) as held_count'))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->groupBy('course_id');

        $attendedSub = DB::table('student_course_attendances')
            ->select('student_id', 'course_id', DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as attended_count"))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->groupBy('student_id', 'course_id');

        $rows = CourseRegistration::query()
            ->join('students', 'course_registrations.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
            ->leftJoin('departments', 'students.department_id', '=', 'departments.id')
            ->leftJoinSub($heldSub, 'held_sessions', fn ($j) => $j->on('course_registrations.course_id', '=', 'held_sessions.course_id'))
            ->leftJoinSub($attendedSub, 'student_att', function ($j) {
                $j->on('course_registrations.student_id', '=', 'student_att.student_id')
                    ->on('course_registrations.course_id', '=', 'student_att.course_id');
            })
            ->when($sessionId, fn ($q) => $q->where('course_registrations.session_id', $sessionId))
            ->when($facultyId, fn ($q) => $q->where('students.faculty_id', $facultyId))
            ->when($departmentId && $departmentId !== 'all', fn ($q) => $q->where('students.department_id', $departmentId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('students.matriculation_number', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('courses.code', 'like', "%{$search}%")
                        ->orWhere('courses.title', 'like', "%{$search}%");
                });
            })
            ->when($threshold === 'eligible', fn ($q) => $q->whereRaw('COALESCE(held_sessions.held_count, 0) > 0')->whereRaw('(COALESCE(student_att.attended_count, 0) * 100.0 / held_sessions.held_count) >= 75'))
            ->when($threshold === 'below', fn ($q) => $q->whereRaw('COALESCE(held_sessions.held_count, 0) > 0')->whereRaw('(COALESCE(student_att.attended_count, 0) * 100.0 / held_sessions.held_count) < 75'))
            ->select([
                'students.matriculation_number',
                'users.name as student_name',
                'departments.name as department_name',
                'courses.code as course_code',
                'courses.title as course_title',
                DB::raw('COALESCE(held_sessions.held_count, 0) as sessions_held'),
                DB::raw('COALESCE(student_att.attended_count, 0) as sessions_attended'),
            ])
            ->orderBy('courses.code')
            ->orderBy('users.name')
            ->get();

        $fileName = 'student_attendance_report_' . now()->format('Y_m_d_His') . '.csv';

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Matric Number', 'Student Name', 'Department', 'Course Code', 'Course Title', 'Attended', 'Sessions Held', 'Percentage']);

            foreach ($rows as $r) {
                $held = (int) $r->sessions_held;
                $attended = (int) $r->sessions_attended;
                $pct = $held > 0 ? round(($attended / $held) * 100, 1) . '%' : 'N/A';

                fputcsv($handle, [
                    $r->matriculation_number,
                    $r->student_name,
                    $r->department_name ?? 'Unassigned',
                    $r->course_code,
                    $r->course_title,
                    $attended,
                    $held,
                    $pct,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }
}
