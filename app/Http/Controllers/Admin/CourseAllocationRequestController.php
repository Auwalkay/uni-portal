<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseAllocation;
use App\Models\CourseAllocationRequest;
use App\Models\Session;
use App\Services\AcademicCacheService;
use App\Services\CourseAllocationNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CourseAllocationRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $staff = $user->staff?->loadMissing('department.faculty');

        $isSuperAdminOrAdmin = $user->hasRole(['admin', 'super_admin']) || $user->can('manage_courses');
        $isDean = ($user->hasRole('dean') || $user->can('view_faculty_courses')) && ! $isSuperAdminOrAdmin;
        $isHod = ($user->hasRole('hod') || $user->can('view_department_courses')) && ! $isSuperAdminOrAdmin && ! $isDean;

        $currentSession = Session::current() ?? Session::latest()->first();
        $sessionId = $request->input('session_id', $currentSession?->id);

        $query = CourseAllocationRequest::query()
            ->with([
                'staff.user:id,name,email',
                'staff.department:id,name,code',
                'course:id,code,title,units,level,semester',
                'department:id,name,code,faculty_id',
                'department.faculty:id,name',
                'session:id,name',
                'reviewer:id,name',
            ]);

        // Scoping
        if ($isHod) {
            $hodDeptId = $staff?->department_id;
            if ($hodDeptId) {
                $query->where('department_id', $hodDeptId);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($isDean) {
            $deanFacultyId = $staff?->department?->faculty_id;
            if ($deanFacultyId) {
                $query->whereHas('department', fn($d) => $d->where('faculty_id', $deanFacultyId));
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($request->filled('department_id') && $request->department_id !== 'ALL') {
            $query->where('department_id', $request->department_id);
        } elseif ($request->filled('faculty_id') && $request->faculty_id !== 'ALL') {
            $query->whereHas('department', fn($d) => $d->where('faculty_id', $request->faculty_id));
        }

        // Filters
        if ($sessionId && $sessionId !== 'ALL') {
            $query->where('session_id', $sessionId);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('course', fn($cq) => $cq->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                    ->orWhereHas('staff.user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('staff', fn($sq) => $sq->where('staff_number', 'like', "%{$search}%"));
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/CourseAllocation/Requests', [
            'requests' => $requests,
            'sessions' => fn() => AcademicCacheService::getSessions(),
            'faculties' => fn() => AcademicCacheService::getFaculties(),
            'filters' => $request->only(['session_id', 'status', 'department_id', 'faculty_id', 'search']),
            'currentSessionId' => $currentSession?->id,
            'isHod' => $isHod,
            'isDean' => $isDean,
        ]);
    }

    public function approve($id)
    {
        $user = auth()->user();
        $staff = $user->staff?->loadMissing('department.faculty');

        $isSuperAdminOrAdmin = $user->hasRole(['admin', 'super_admin']) || $user->can('manage_courses');
        $isDean = ($user->hasRole('dean') || $user->can('view_faculty_courses')) && ! $isSuperAdminOrAdmin;
        $isHod = ($user->hasRole('hod') || $user->can('view_department_courses')) && ! $isSuperAdminOrAdmin && ! $isDean;

        $allocationRequest = CourseAllocationRequest::with('course')->findOrFail($id);

        if ($allocationRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        // Scoping validation
        if ($isHod && $allocationRequest->department_id !== $staff?->department_id) {
            abort(403, 'You can only approve course requests for your own department.');
        }

        if ($isDean && $allocationRequest->department?->faculty_id !== $staff?->department?->faculty_id) {
            abort(403, 'You can only approve course requests for your own faculty.');
        }

        try {
            DB::transaction(function () use ($allocationRequest, $user) {
                // Ensure course is not already allocated
                $alreadyAllocated = CourseAllocation::where('course_id', $allocationRequest->course_id)
                    ->where('session_id', $allocationRequest->session_id)
                    ->exists();

                if ($alreadyAllocated) {
                    throw new \DomainException('This course is already allocated to another lecturer in this session.');
                }

                // Create CourseAllocation
                CourseAllocation::create([
                    'course_id' => $allocationRequest->course_id,
                    'staff_id' => $allocationRequest->staff_id,
                    'session_id' => $allocationRequest->session_id,
                    'department_id' => $allocationRequest->department_id,
                    'program_id' => $allocationRequest->course->programme_id ?? null,
                ]);

                // Update request status
                $allocationRequest->update([
                    'status' => 'approved',
                    'actioned_by' => $user->id,
                    'actioned_at' => now(),
                ]);
            });

            AcademicCacheService::clearTimetableCache();

            // Notify lecturer
            CourseAllocationNotificationService::notifyApproved($allocationRequest);

            return back()->with('success', 'Course allocation request approved successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $user = auth()->user();
        $staff = $user->staff?->loadMissing('department.faculty');

        $isSuperAdminOrAdmin = $user->hasRole(['admin', 'super_admin']) || $user->can('manage_courses');
        $isDean = ($user->hasRole('dean') || $user->can('view_faculty_courses')) && ! $isSuperAdminOrAdmin;
        $isHod = ($user->hasRole('hod') || $user->can('view_department_courses')) && ! $isSuperAdminOrAdmin && ! $isDean;

        $allocationRequest = CourseAllocationRequest::findOrFail($id);

        if ($allocationRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        if ($isHod && $allocationRequest->department_id !== $staff?->department_id) {
            abort(403, 'You can only reject course requests for your own department.');
        }

        if ($isDean && $allocationRequest->department?->faculty_id !== $staff?->department?->faculty_id) {
            abort(403, 'You can only reject course requests for your own faculty.');
        }

        $reason = $request->input('reason', $request->input('rejection_reason'));

        $allocationRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'actioned_by' => $user->id,
            'actioned_at' => now(),
        ]);

        // Notify lecturer
        CourseAllocationNotificationService::notifyRejected($allocationRequest);

        return back()->with('success', 'Course allocation request has been rejected.');
    }

    public function batchApprove(Request $request)
    {
        $request->validate([
            'request_ids' => 'required|array|min:1',
            'request_ids.*' => 'exists:course_allocation_requests,id',
        ]);

        $user = auth()->user();
        $staff = $user->staff?->loadMissing('department.faculty');

        $isSuperAdminOrAdmin = $user->hasRole(['admin', 'super_admin']) || $user->can('manage_courses');
        $isDean = ($user->hasRole('dean') || $user->can('view_faculty_courses')) && ! $isSuperAdminOrAdmin;
        $isHod = ($user->hasRole('hod') || $user->can('view_department_courses')) && ! $isSuperAdminOrAdmin && ! $isDean;

        $requests = CourseAllocationRequest::with('course')
            ->whereIn('id', $request->request_ids)
            ->where('status', 'pending')
            ->get();

        $approvedCount = 0;
        $skippedCount = 0;

        foreach ($requests as $allocationRequest) {
            if ($isHod && $allocationRequest->department_id !== $staff?->department_id) {
                $skippedCount++;
                continue;
            }

            if ($isDean && $allocationRequest->department?->faculty_id !== $staff?->department?->faculty_id) {
                $skippedCount++;
                continue;
            }

            try {
                DB::transaction(function () use ($allocationRequest, $user) {
                    $alreadyAllocated = CourseAllocation::where('course_id', $allocationRequest->course_id)
                        ->where('session_id', $allocationRequest->session_id)
                        ->exists();

                    if (! $alreadyAllocated) {
                        CourseAllocation::create([
                            'course_id' => $allocationRequest->course_id,
                            'staff_id' => $allocationRequest->staff_id,
                            'session_id' => $allocationRequest->session_id,
                            'department_id' => $allocationRequest->department_id,
                            'program_id' => $allocationRequest->course->programme_id ?? null,
                        ]);

                        $allocationRequest->update([
                            'status' => 'approved',
                            'actioned_by' => $user->id,
                            'actioned_at' => now(),
                        ]);
                    }
                });

                CourseAllocationNotificationService::notifyApproved($allocationRequest);
                $approvedCount++;
            } catch (\Throwable $e) {
                $skippedCount++;
            }
        }

        AcademicCacheService::clearTimetableCache();

        $message = "{$approvedCount} course request(s) approved successfully.";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} skipped due to existing allocation or authorization)";
        }

        return back()->with('success', $message);
    }
}
