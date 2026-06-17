<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ResultImport;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Semester;
use App\Models\Session;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ResultController extends Controller
{
    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function index(Request $request)
    {
        $currentSession = \App\Services\AcademicCacheService::getCurrentSession();

        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedSemesterId = $request->input('semester_id');
        $selectedDepartmentId = $request->input('department_id');
        $selectedLevel = $request->input('level');
        $hasRegistrations = $request->boolean('has_registrations');
        $publishStatus = $request->input('publish_status', 'all');
        $sortBy = $request->input('sort_by', 'code');
        $sortDir = $request->input('sort_dir', 'asc');

        if (! in_array($sortBy, ['code', 'level', 'department'])) {
            $sortBy = 'code';
        }
        if (! in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $user = auth()->user();
        $courses = Course::query()
            ->select('courses.*')
            ->with(['department', 'program'])
            ->when(! $user->can('manage_results'), function ($query) use ($user, $selectedSessionId) {
                $query->whereHas('allocations', function ($q) use ($user, $selectedSessionId) {
                    $q->whereHas('staff', fn ($sq) => $sq->where('user_id', $user->id));
                    if ($selectedSessionId) {
                        $q->where('session_id', $selectedSessionId);
                    }
                });
            })
            ->when($selectedDepartmentId, function ($query, $deptId) {
                $query->where('courses.department_id', $deptId);
            })
            ->when($selectedLevel, function ($query, $level) {
                $query->where('courses.level', $level);
            })
            ->when($hasRegistrations, function ($query) use ($selectedSessionId, $selectedSemesterId) {
                $query->whereHas('registrations', function ($q) use ($selectedSessionId, $selectedSemesterId) {
                    $q->where('session_id', $selectedSessionId)
                        ->when($selectedSemesterId, fn ($sq) => $sq->where('semester_id', $selectedSemesterId));
                });
            })
            ->when($selectedSemesterId, function ($query, $semesterId) use ($selectedSessionId) {
                $query->whereHas('registrations', function ($q) use ($selectedSessionId, $semesterId) {
                    $q->where('session_id', $selectedSessionId)
                        ->where('semester_id', $semesterId);
                });
            })
            ->when($publishStatus === 'published', function ($query) use ($selectedSessionId, $selectedSemesterId) {
                $query->whereHas('registrations', function ($q) use ($selectedSessionId, $selectedSemesterId) {
                    $q->where('session_id', $selectedSessionId)
                        ->when($selectedSemesterId, fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
                        ->where('is_published', true);
                });
            })
            ->when($publishStatus === 'unpublished', function ($query) use ($selectedSessionId, $selectedSemesterId) {
                $query->whereDoesntHave('registrations', function ($q) use ($selectedSessionId, $selectedSemesterId) {
                    $q->where('session_id', $selectedSessionId)
                        ->when($selectedSemesterId, fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
                        ->where('is_published', true);
                });
            })
            // Optimization: Load results stats for the selected session and semester
            ->withCount([
                'registrations as graded_count' => function ($query) use ($selectedSessionId, $selectedSemesterId) {
                    $query->where('session_id', $selectedSessionId)
                        ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
                        ->where(function ($q) {
                            $q->whereNotNull('score')
                                ->orWhere('is_absent', true);
                        });
                },
            ])
            ->withCount([
                'registrations as total_students' => function ($query) use ($selectedSessionId, $selectedSemesterId) {
                    $query->where('session_id', $selectedSessionId)
                        ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId));
                },
            ])
            ->withCount([
                'registrations as published_count' => function ($query) use ($selectedSessionId, $selectedSemesterId) {
                    $query->where('session_id', $selectedSessionId)
                        ->when($selectedSemesterId, fn ($q) => $q->where('semester_id', $selectedSemesterId))
                        ->where('is_published', true);
                },
            ])
            ->when($sortBy === 'department', function ($query) use ($sortDir) {
                $query->leftJoin('departments', 'courses.department_id', '=', 'departments.id')
                    ->orderBy('departments.name', $sortDir);
            }, function ($query) use ($sortBy, $sortDir) {
                $query->orderBy('courses.'.$sortBy, $sortDir);
            })
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Results/Index', [
            'sessions' => fn () => \App\Services\AcademicCacheService::getSessions(),
            'semesters' => fn () => Semester::when($selectedSessionId, fn ($q) => $q->where('session_id', $selectedSessionId))->get(),
            'departments' => fn () => \App\Services\AcademicCacheService::getAllDepartments(),
            'faculties' => fn () => \App\Services\AcademicCacheService::getAllFaculties(),
            'courses' => $courses,
            'filters' => [
                'session_id' => $selectedSessionId,
                'semester_id' => $selectedSemesterId,
                'department_id' => $selectedDepartmentId,
                'level' => $selectedLevel,
                'has_registrations' => $hasRegistrations,
                'publish_status' => $publishStatus,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'can' => [
                'publish_results' => $user->can('publish_results') || $user->hasRole('admin'),
            ],
        ]);
    }

    public function publish(Request $request, Course $course)
    {
        $user = auth()->user();
        if (! $user->can('publish_results') && ! $user->hasRole('admin')) {
            abort(403, 'You do not have permission to publish results.');
        }

        $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'is_published' => 'required|boolean',
        ]);

        CourseRegistration::where('course_id', $course->id)
            ->where('session_id', $request->session_id)
            ->update(['is_published' => $request->is_published]);

        $status = $request->is_published ? 'published' : 'unpublished';

        return back()->with('success', "Results for {$course->code} have been {$status}.");
    }

    public function publishSession(Request $request, Session $session)
    {
        $user = auth()->user();
        if (! $user->can('publish_results') && ! $user->hasRole('admin')) {
            abort(403, 'You do not have permission to publish results.');
        }

        $request->validate([
            'is_published' => 'required|boolean',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
        ]);

        $isPublished = $request->boolean('is_published');
        $departmentId = $request->input('department_id');
        $level = $request->input('level');

        // Build base query for Course Registrations
        $query = CourseRegistration::query()
            ->where('session_id', $session->id);

        if ($departmentId || $level) {
            $query->whereHas('course', function ($q) use ($departmentId, $level) {
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
                if ($level) {
                    $q->where('level', $level);
                }
            });
        }

        if ($isPublished) {
            // Find graded courses matching selected filters in this session
            $gradedCourseIds = CourseRegistration::where('session_id', $session->id)
                ->where(function ($q) {
                    $q->whereNotNull('score')
                        ->orWhere('is_absent', true);
                })
                ->when($departmentId || $level, function ($q) use ($departmentId, $level) {
                    $q->whereHas('course', function ($cq) use ($departmentId, $level) {
                        if ($departmentId) {
                            $cq->where('department_id', $departmentId);
                        }
                        if ($level) {
                            $cq->where('level', $level);
                        }
                    });
                })
                ->distinct()
                ->pluck('course_id');

            $query->whereIn('course_id', $gradedCourseIds)
                ->update(['is_published' => true]);

            $message = 'Results successfully published for graded courses matching selected filters.';
        } else {
            // Unpublish all registrations matching filters
            $query->update(['is_published' => false]);

            $message = 'Results successfully unpublished for registrations matching selected filters.';
        }

        return back()->with('success', $message);
    }

    public function edit(Course $course, Request $request)
    {
        $user = auth()->user();
        if (! $user->can('manage_results')) {
            $allocated = \App\Models\CourseAllocation::where('course_id', $course->id)
                ->whereHas('staff', fn ($q) => $q->where('user_id', $user->id))
                ->exists();

            if (! $allocated) {
                abort(403, 'You are not allocated to this course.');
            }
        }

        // Allow passing session_id, default to current
        $sessionId = $request->input('session_id');
        if ($sessionId) {
            $session = Session::findOrFail($sessionId);
        } else {
            $session = Session::where('is_current', true)->firstOrFail();
        }

        $registrations = CourseRegistration::where('course_id', $course->id)
            ->where('session_id', $session->id)
            ->with(['student.user'])
            ->orderBy('student_id') // Roughly by registration or matric
            ->get();

        return Inertia::render('Admin/Results/Entry', [
            'course' => $course,
            'session' => $session,
            'registrations' => $registrations,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $user = auth()->user();
        if (! $user->can('manage_results')) {
            $allocated = \App\Models\CourseAllocation::where('course_id', $course->id)
                ->whereHas('staff', fn ($q) => $q->where('user_id', $user->id))
                ->exists();

            if (! $allocated) {
                abort(403, 'You are not allocated to this course.');
            }
        }
        $request->validate([
            'scores' => 'required|array',
            'scores.*.id' => 'required|exists:course_registrations,id',
            'scores.*.ca_score' => 'nullable|numeric|min:0|max:40',
            'scores.*.exam_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.is_absent' => 'nullable|boolean',
        ]);

        foreach ($request->scores as $data) {
            $reg = CourseRegistration::find($data['id']);

            if (! empty($data['is_absent'])) {
                $reg->update([
                    'ca_score' => 0,
                    'exam_score' => 0,
                    'score' => null,
                    'grade' => 'ABS',
                    'grade_point' => 0.00,
                    'is_absent' => true,
                ]);
            } else {
                $ca = $data['ca_score'] ?? 0;
                $exam = $data['exam_score'] ?? 0;
                $total = $ca + $exam;

                // Simple validation clamp
                if ($total > 100) {
                    $total = 100;
                }

                $grading = $this->gradingService->calculate($total);

                $reg->update([
                    'ca_score' => $ca,
                    'exam_score' => $exam,
                    'score' => $total,
                    'grade' => $grading['grade'],
                    'grade_point' => $grading['point'],
                    'is_absent' => false,
                ]);
            }
        }

        return back()->with('success', 'Results updated successfully.');
    }

    public function upload(Request $request, Course $course)
    {
        $user = auth()->user();
        if (! $user->can('manage_results')) {
            $allocated = \App\Models\CourseAllocation::where('course_id', $course->id)
                ->whereHas('staff', fn ($q) => $q->where('user_id', $user->id))
                ->exists();

            if (! $allocated) {
                abort(403, 'You are not allocated to this course.');
            }
        }
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'session_id' => 'required|exists:academic_sessions,id',
        ]);

        $session = Session::findOrFail($request->session_id);

        try {
            Excel::import(new ResultImport($course, $session, $this->gradingService), $request->file('file'));

            return back()->with('success', 'Results imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function print(Request $request)
    {
        $user = auth()->user();

        $selectedSessionId = $request->input('session_id');
        $selectedSemesterId = $request->input('semester_id');
        $selectedDepartmentId = $request->input('department_id');
        $selectedLevel = $request->input('level');
        $hasRegistrations = $request->boolean('has_registrations');
        $publishStatus = $request->input('publish_status', 'all');
        $courseId = $request->input('course_id');

        if ($selectedSessionId && $selectedSessionId !== 'ALL') {
            $session = Session::find($selectedSessionId);
        } else {
            $session = Session::where('is_current', true)->first();
        }
        if (! $session) {
            $session = Session::latest()->first();
        }
        if (! $session) {
            abort(404, 'Active academic session not found.');
        }

        // Fetch courses based on filters
        $coursesQuery = Course::query()
            ->with(['department', 'program'])
            ->when(! $user->can('manage_results'), function ($query) use ($user, $session) {
                $query->whereHas('allocations', function ($q) use ($user, $session) {
                    $q->whereHas('staff', fn ($sq) => $sq->where('user_id', $user->id));
                    $q->where('session_id', $session->id);
                });
            })
            ->when($courseId, function ($query, $id) {
                $query->where('courses.id', $id);
            })
            ->when($selectedDepartmentId && $selectedDepartmentId !== 'ALL', function ($query, $deptId) {
                $query->where('courses.department_id', $deptId);
            })
            ->when($selectedLevel && $selectedLevel !== 'ALL', function ($query, $level) {
                $query->where('courses.level', $level);
            })
            ->when($hasRegistrations, function ($query) use ($session, $selectedSemesterId) {
                $query->whereHas('registrations', function ($q) use ($session, $selectedSemesterId) {
                    $q->where('session_id', $session->id)
                        ->when($selectedSemesterId && $selectedSemesterId !== 'ALL', fn ($sq) => $sq->where('semester_id', $selectedSemesterId));
                });
            })
            ->when($selectedSemesterId && $selectedSemesterId !== 'ALL', function ($query, $semesterId) use ($session) {
                $query->whereHas('registrations', function ($q) use ($session, $semesterId) {
                    $q->where('session_id', $session->id)
                        ->where('semester_id', $semesterId);
                });
            })
            ->when($publishStatus === 'published', function ($query) use ($session, $selectedSemesterId) {
                $query->whereHas('registrations', function ($q) use ($session, $selectedSemesterId) {
                    $q->where('session_id', $session->id)
                        ->when($selectedSemesterId && $selectedSemesterId !== 'ALL', fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
                        ->where('is_published', true);
                });
            })
            ->when($publishStatus === 'unpublished', function ($query) use ($session, $selectedSemesterId) {
                $query->whereDoesntHave('registrations', function ($q) use ($session, $selectedSemesterId) {
                    $q->where('session_id', $session->id)
                        ->when($selectedSemesterId && $selectedSemesterId !== 'ALL', fn ($sq) => $sq->where('semester_id', $selectedSemesterId))
                        ->where('is_published', true);
                });
            });

        $courses = $coursesQuery->orderBy('code', 'asc')->get();

        $coursesData = [];
        foreach ($courses as $course) {
            $registrations = CourseRegistration::where('course_id', $course->id)
                ->where('session_id', $session->id)
                ->when($selectedSemesterId && $selectedSemesterId !== 'ALL', fn ($q) => $q->where('semester_id', $selectedSemesterId))
                ->with(['student.user'])
                ->get()
                ->sortBy(function ($reg) {
                    return $reg->student?->matriculation_number ?? '';
                });

            // If a specific course_id is asked, we do not skip even if empty
            if ($registrations->isEmpty() && ! $courseId && ($hasRegistrations || $request->has('skip_empty') || $request->boolean('skip_empty', true))) {
                continue;
            }

            // Calculate stats
            $totalStudents = $registrations->count();
            $gradedCount = $registrations->filter(function ($reg) {
                return $reg->is_absent || ! is_null($reg->score);
            })->count();

            $passCount = $registrations->filter(function ($reg) {
                return ! $reg->is_absent && ! is_null($reg->score) && $reg->score >= 40;
            })->count();

            $failCount = $registrations->filter(function ($reg) {
                return ! $reg->is_absent && ! is_null($reg->score) && $reg->score < 40;
            })->count();

            $absentCount = $registrations->where('is_absent', true)->count();

            $gradedForAvg = $registrations->filter(function ($reg) {
                return ! $reg->is_absent && ! is_null($reg->score);
            });

            $avgScore = $gradedForAvg->count() > 0
                ? round($gradedForAvg->avg('score'), 1)
                : 0;

            $coursesData[] = [
                'course' => $course,
                'registrations' => $registrations,
                'stats' => [
                    'total' => $totalStudents,
                    'graded' => $gradedCount,
                    'passes' => $passCount,
                    'fails' => $failCount,
                    'absents' => $absentCount,
                    'average' => $avgScore,
                ],
            ];
        }

        if (empty($coursesData)) {
            return back()->with('error', 'No course results found matching the current filters.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.course_results', [
            'session' => $session,
            'coursesData' => $coursesData,
            'date' => now()->format('d M, Y h:i A'),
        ]);

        $filename = count($coursesData) === 1
            ? $coursesData[0]['course']->code.'_results_'.$session->name.'.pdf'
            : 'compiled_results_'.$session->name.'.pdf';

        return $pdf->stream($filename);
    }
}
