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

        if (! $staff || ! $staff->department_id) {
            return Inertia::render('Staff/Course/Allocation', [
                'hasDepartment' => false,
                'isOpen' => false,
                'courses' => ['data' => [], 'links' => []],
                'requests' => [],
                'stats' => [
                    'allocated_units' => 0,
                    'pending_units' => 0,
                    'total_units' => 0,
                    'min_units' => 6,
                    'max_units' => 24,
                    'max_courses' => 5,
                    'allocated_courses_count' => 0,
                    'pending_courses_count' => 0,
                ],
                'currentSession' => null,
                'filters' => [],
            ]);
        }

        $session = Session::current() ?? Session::latest()->first();
        $sessionId = $session?->id;

        $isOpen = filter_var(SystemSetting::get('enable_lecturer_self_course_allocation', true), FILTER_VALIDATE_BOOLEAN);
        $minUnits = intval(SystemSetting::get('lecturer_self_allocation_min_units', 6));
        $maxUnits = intval(SystemSetting::get('lecturer_self_allocation_max_units', 24));
        $maxCourses = intval(SystemSetting::get('lecturer_self_allocation_max_courses', 5));

        // High scale O(1) query strategy: Fetch department allocations & requests for current session
        $allocations = CourseAllocation::where('session_id', $sessionId)
            ->where('department_id', $staff->department_id)
            ->with('staff.user:id,name')
            ->get(['id', 'course_id', 'staff_id']);

        $pendingRequests = CourseAllocationRequest::where('session_id', $sessionId)
            ->where('department_id', $staff->department_id)
            ->where('status', 'pending')
            ->with('staff.user:id,name')
            ->get(['id', 'course_id', 'staff_id', 'created_at']);

        $allocationsByCourse = $allocations->keyBy('course_id');
        $requestsByCourse = $pendingRequests->keyBy('course_id');

        // Calculate Lecturer's Current Workload
        $myAllocatedCourseIds = $allocations->where('staff_id', $staff->id)->pluck('course_id');
        $myPendingCourseIds = $pendingRequests->where('staff_id', $staff->id)->pluck('course_id');

        $allocatedUnits = Course::whereIn('id', $myAllocatedCourseIds)->sum('units');
        $pendingUnits = Course::whereIn('id', $myPendingCourseIds)->sum('units');
        $totalUnits = $allocatedUnits + $pendingUnits;

        // Query Department Courses
        $filters = $request->only(['search', 'level', 'semester']);
        $courses = Course::where('department_id', $staff->department_id)
            ->where('is_active', true)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['level'] ?? null, fn($q, $lvl) => $q->where('level', $lvl))
            ->when($filters['semester'] ?? null, fn($q, $sem) => $q->where('semester', (string) $sem))
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString()
            ->through(function ($course) use ($staff, $allocationsByCourse, $requestsByCourse) {
                $allocation = $allocationsByCourse->get($course->id);
                $pendingRequest = $requestsByCourse->get($course->id);

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
                ];
            });

        // Lecturer's Requests History for Session
        $myRequests = CourseAllocationRequest::where('staff_id', $staff->id)
            ->where('session_id', $sessionId)
            ->with(['course:id,code,title,units', 'reviewer:id,name'])
            ->latest()
            ->get();

        return Inertia::render('Staff/Course/Allocation', [
            'hasDepartment' => true,
            'department' => $staff->department->only(['id', 'name', 'code']),
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
            'filters' => $filters,
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
        if (! $staff || ! $staff->department_id) {
            return back()->with('error', 'You must have an assigned academic department to request courses.');
        }

        try {
            $allocationRequest = DB::transaction(function () use ($staff, $session, $request) {
                // Pessimistic Lock on the course to serialize concurrent picks for the same course
                $course = Course::where('id', $request->course_id)
                    ->where('department_id', $staff->department_id)
                    ->lockForUpdate()
                    ->first();

                if (! $course) {
                    throw new \DomainException('This course does not belong to your department or is inactive.');
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
                    'department_id' => $staff->department_id,
                    'status' => 'pending',
                    'notes' => $request->input('notes'),
                ]);
            });

            // Dispatch emails to HOD, Dean, and Admins
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
