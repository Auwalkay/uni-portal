<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseAllocationRequest;
use App\Models\Session;
use App\Models\SystemSetting;
use App\Services\CourseAllocationNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SelfCourseAllocationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $staff = $user->staff()->with('department.faculty')->first();

        if (! $staff) {
            return redirect()->route('admin.dashboard')->with('error', 'Staff profile required.');
        }

        $session = Session::current() ?? Session::latest()->first();
        $sessionId = $session?->id;

        $isOpen = filter_var(SystemSetting::get('enable_lecturer_self_course_allocation', true), FILTER_VALIDATE_BOOLEAN);
        $minUnits = intval(SystemSetting::get('lecturer_self_allocation_min_units', 6));
        $maxUnits = intval(SystemSetting::get('lecturer_self_allocation_max_units', 24));
        $maxCourses = intval(SystemSetting::get('lecturer_self_allocation_max_courses', 5));

        // Filters: search, level, semester, department_id
        $filters = $request->only(['search', 'level', 'semester', 'department_id']);
        $selectedDepartmentId = $request->has('department_id')
            ? $request->input('department_id')
            : ($staff->department_id ?? 'all');

        // Calculate Lecturer's Current Workload across all departments
        $myAllocatedCourseIds = CourseAllocation::where('session_id', $sessionId)
            ->where('staff_id', $staff->id)
            ->pluck('course_id');

        $myPendingCourseIds = CourseAllocationRequest::where('session_id', $sessionId)
            ->where('staff_id', $staff->id)
            ->where('status', 'pending')
            ->pluck('course_id');

        $allocatedUnits = Course::whereIn('id', $myAllocatedCourseIds)->sum('units');
        $pendingUnits = Course::whereIn('id', $myPendingCourseIds)->sum('units');
        $totalUnits = $allocatedUnits + $pendingUnits;

        // Query Courses with department eager loaded
        $coursesQuery = Course::query()
            ->with(['department:id,name,code', 'department.faculty:id,name'])
            ->where('is_active', true)
            ->when($selectedDepartmentId && $selectedDepartmentId !== 'all', function ($q) use ($selectedDepartmentId) {
                $q->where('department_id', $selectedDepartmentId);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['level'] ?? null, fn($q, $lvl) => $q->where('level', $lvl))
            ->when($filters['semester'] ?? null, fn($q, $sem) => $q->where('semester', (string) $sem))
            ->orderBy('code');

        $paginatedCourses = $coursesQuery->paginate(12)->withQueryString();

        // High scale O(1) page-level allocation & request resolution
        $pageCourseIds = $paginatedCourses->pluck('id');

        $allocations = CourseAllocation::where('session_id', $sessionId)
            ->whereIn('course_id', $pageCourseIds)
            ->with('staff.user:id,name')
            ->get(['id', 'course_id', 'staff_id'])
            ->keyBy('course_id');

        $pendingRequests = CourseAllocationRequest::where('session_id', $sessionId)
            ->whereIn('course_id', $pageCourseIds)
            ->where('status', 'pending')
            ->with('staff.user:id,name')
            ->get(['id', 'course_id', 'staff_id', 'created_at'])
            ->keyBy('course_id');

        $courses = $paginatedCourses->through(function ($course) use ($staff, $allocations, $pendingRequests) {
            $allocation = $allocations->get($course->id);
            $pendingRequest = $pendingRequests->get($course->id);

            $status = 'available';
            $holderName = null;

            if ($allocation) {
                if ($allocation->staff_id === $staff->id) {
                    $status = 'allocated_to_me';
                    $holderName = 'You';
                } else {
                    $status = 'taken_by_other';
                    $holderName = $allocation->staff?->user?->name ?? 'Another Lecturer';
                }
            } elseif ($pendingRequest) {
                if ($pendingRequest->staff_id === $staff->id) {
                    $status = 'pending_by_me';
                    $holderName = 'You (Pending)';
                } else {
                    $status = 'taken_by_other';
                    $holderName = ($pendingRequest->staff?->user?->name ?? 'Another Lecturer') . ' (Requested)';
                }
            }

            return [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'units' => $course->units,
                'level' => $course->level,
                'semester' => $course->semester,
                'status' => $status,
                'holder_name' => $holderName,
                'department' => $course->department ? $course->department->only(['id', 'name', 'code']) : null,
            ];
        });

        // Lecturer's Requests History for Session
        $myRequests = CourseAllocationRequest::where('staff_id', $staff->id)
            ->where('session_id', $sessionId)
            ->with(['course:id,code,title,units', 'course.department:id,name,code', 'department:id,name,code', 'reviewer:id,name'])
            ->latest()
            ->get();

        $departments = \App\Services\AcademicCacheService::getAcademicDepartments(true)
            ->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
            ]);

        return Inertia::render('Staff/Course/Allocation', [
            'hasDepartment' => (bool) $staff->department_id,
            'department' => $staff->department ? $staff->department->only(['id', 'name', 'code']) : null,
            'departments' => $departments,
            'isOpen' => $isOpen,
            'courses' => $courses,
            'requests' => $myRequests,
            'stats' => [
                'allocated_units' => intval($allocatedUnits),
                'pending_units' => intval($pendingUnits),
                'total_units' => intval($totalUnits),
                'min_units' => $minUnits,
                'max_units' => $maxUnits,
                'max_courses' => $maxCourses,
                'allocated_courses_count' => $myAllocatedCourseIds->count(),
                'pending_courses_count' => $myPendingCourseIds->count(),
            ],
            'currentSession' => $session ? $session->only(['id', 'name']) : null,
            'filters' => array_merge($filters, ['department_id' => $selectedDepartmentId]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $isOpen = filter_var(SystemSetting::get('enable_lecturer_self_course_allocation', true), FILTER_VALIDATE_BOOLEAN);
        if (! $isOpen) {
            return back()->with('error', 'Self course allocation is currently closed by the administration.');
        }

        $session = Session::current() ?? Session::latest()->first();
        if (! $session) {
            return back()->with('error', 'No active academic session found.');
        }

        $user = auth()->user();
        $staff = $user->staff;
        if (! $staff) {
            return back()->with('error', 'You must have an active staff profile to request courses.');
        }

        try {
            $allocationRequest = DB::transaction(function () use ($staff, $session, $request) {
                // Pessimistic Lock on the course to serialize concurrent picks for the same course
                // Allow any active course across departments
                $course = Course::where('id', $request->course_id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $course) {
                    throw new \DomainException('This course does not exist or is inactive.');
                }

                // Check Exclusivity: Active allocation in this session
                $isAllocated = CourseAllocation::where('course_id', $course->id)
                    ->where('session_id', $session->id)
                    ->exists();

                if ($isAllocated) {
                    throw new \DomainException('This course is already allocated to a lecturer for this session.');
                }

                // Check Exclusivity: Active pending or approved request
                $existingRequest = CourseAllocationRequest::where('course_id', $course->id)
                    ->where('session_id', $session->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->first();

                if ($existingRequest) {
                    if ($existingRequest->staff_id === $staff->id) {
                        throw new \DomainException('You already have a pending or approved request for this course.');
                    }
                    throw new \DomainException('Another lecturer has already requested this course.');
                }

                // Check Max Workload
                $maxUnits = intval(SystemSetting::get('lecturer_self_allocation_max_units', 24));
                $maxCourses = intval(SystemSetting::get('lecturer_self_allocation_max_courses', 5));

                $allocatedUnits = CourseAllocation::where('staff_id', $staff->id)
                    ->where('session_id', $session->id)
                    ->join('courses', 'courses.id', '=', 'course_allocations.course_id')
                    ->sum('courses.units');

                $pendingUnits = CourseAllocationRequest::where('staff_id', $staff->id)
                    ->where('session_id', $session->id)
                    ->where('status', 'pending')
                    ->join('courses', 'courses.id', '=', 'course_allocation_requests.course_id')
                    ->sum('courses.units');

                if (($allocatedUnits + $pendingUnits + $course->units) > $maxUnits) {
                    throw new \DomainException("Requesting this course ({$course->units} units) would exceed your maximum allowed limit of {$maxUnits} units.");
                }

                $totalCount = CourseAllocation::where('staff_id', $staff->id)->where('session_id', $session->id)->count()
                    + CourseAllocationRequest::where('staff_id', $staff->id)->where('session_id', $session->id)->where('status', 'pending')->count();

                if ($totalCount >= $maxCourses) {
                    throw new \DomainException("You have reached the maximum allowed limit of {$maxCourses} courses.");
                }

                return CourseAllocationRequest::create([
                    'staff_id' => $staff->id,
                    'course_id' => $course->id,
                    'session_id' => $session->id,
                    'department_id' => $course->department_id,
                    'status' => 'pending',
                    'notes' => $request->input('notes'),
                ]);
            });

            // Dispatch emails to HOD, Dean, and Admins of the offering department
            CourseAllocationNotificationService::notifyReviewers($allocationRequest);

            return back()->with('success', 'Course request submitted successfully and sent for review.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'An error occurred while submitting your request: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        $user = auth()->user();
        $staff = $user->staff;

        $allocationRequest = CourseAllocationRequest::where('id', $id)
            ->where('staff_id', $staff?->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $allocationRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Course allocation request cancelled.');
    }
}
