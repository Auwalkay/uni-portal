<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Session;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ResultImport;

class ResultController extends Controller
{
    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function index(Request $request)
    {
        $sessions = Session::orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $faculties = Faculty::orderBy('name')->get();

        $currentSession = Session::where('is_current', true)->first();

        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedDepartmentId = $request->input('department_id');
        $selectedLevel = $request->input('level');
        $hasRegistrations = $request->boolean('has_registrations');

        $user = auth()->user();
        $courses = Course::query()
            ->with(['department', 'program'])
            ->when(!$user->can('manage_results'), function ($query) use ($user, $selectedSessionId) {
                $query->whereHas('allocations', function ($q) use ($user, $selectedSessionId) {
                    $q->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                    if ($selectedSessionId) {
                        $q->where('session_id', $selectedSessionId);
                    }
                });
            })
            ->when($selectedDepartmentId, function ($query, $deptId) {
                $query->where('department_id', $deptId);
            })
            ->when($selectedLevel, function ($query, $level) {
                $query->where('level', $level);
            })
            ->when($hasRegistrations, function ($query) use ($selectedSessionId) {
                $query->whereHas('registrations', function ($q) use ($selectedSessionId) {
                    $q->where('session_id', $selectedSessionId);
                });
            })
            // Optimization: Load results stats for the selected session
            ->withCount([
                'registrations as graded_count' => function ($query) use ($selectedSessionId) {
                    $query->where('session_id', $selectedSessionId)
                        ->where(function ($q) {
                            $q->whereNotNull('score')
                              ->orWhere('is_absent', true);
                        });
                }
            ])
            ->withCount([
                'registrations as total_students' => function ($query) use ($selectedSessionId) {
                    $query->where('session_id', $selectedSessionId);
                }
            ])
            ->withCount([
                'registrations as published_count' => function ($query) use ($selectedSessionId) {
                    $query->where('session_id', $selectedSessionId)
                        ->where('is_published', true);
                }
            ])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Results/Index', [
            'sessions' => $sessions,
            'departments' => $departments,
            'faculties' => $faculties, // Optional if we want filtering by faculty
            'courses' => $courses,
            'filters' => [
                'session_id' => $selectedSessionId,
                'department_id' => $selectedDepartmentId,
                'level' => $selectedLevel,
                'has_registrations' => $hasRegistrations,
            ],
            'can' => [
                'publish_results' => $user->can('publish_results') || $user->hasRole('admin'),
            ]
        ]);
    }

    public function publish(Request $request, Course $course)
    {
        $user = auth()->user();
        if (!$user->can('publish_results') && !$user->hasRole('admin')) {
            abort(403, 'You do not have permission to publish results.');
        }

        $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'is_published' => 'required|boolean'
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
        if (!$user->can('publish_results') && !$user->hasRole('admin')) {
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
        if (!$user->can('manage_results')) {
            $allocated = \App\Models\CourseAllocation::where('course_id', $course->id)
                ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
                ->exists();
            
            if (!$allocated) {
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
        if (!$user->can('manage_results')) {
            $allocated = \App\Models\CourseAllocation::where('course_id', $course->id)
                ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
                ->exists();
            
            if (!$allocated) {
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

            if (!empty($data['is_absent'])) {
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
                if ($total > 100)
                    $total = 100;

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
        if (!$user->can('manage_results')) {
            $allocated = \App\Models\CourseAllocation::where('course_id', $course->id)
                ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
                ->exists();
            
            if (!$allocated) {
                abort(403, 'You are not allocated to this course.');
            }
        }
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'session_id' => 'required|exists:academic_sessions,id'
        ]);

        $session = Session::findOrFail($request->session_id);

        try {
            Excel::import(new ResultImport($course, $session, $this->gradingService), $request->file('file'));
            return back()->with('success', 'Results imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
