<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Session;
use App\Models\Staff;
use Inertia\Inertia;

class CourseAllocationController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $staff = $user->staff?->loadMissing('department.faculty');
        $isSuperAdminOrAdmin = $user->hasRole(['admin', 'super_admin']);
        $isDean = ! $isSuperAdminOrAdmin && ($user->hasRole('dean') || $user->can('view_faculty_courses'));
        $isHod = ! $isSuperAdminOrAdmin && ! $isDean && ($user->hasRole('hod') || $user->can('view_department_courses'));
        $hodDeptId = $staff?->department_id;
        $deanFacultyId = $staff?->department?->faculty_id;

        $currentSessionId = Session::current()?->id;
        $sessionId = $request->input('session_id', $currentSessionId);

        $allocationsQuery = \App\Models\CourseAllocation::query()
            ->with([
                'course.department.faculty',
                'staff.user',
                'staff.department',
                'session',
                'department.faculty',
                'programme',
            ])
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId));

        if ($isHod) {
            if ($hodDeptId) {
                $allocationsQuery->where(function ($sub) use ($hodDeptId) {
                    $sub->where('department_id', $hodDeptId)
                        ->orWhereHas('course', fn($c) => $c->where('department_id', $hodDeptId));
                });
            } else {
                $allocationsQuery->whereRaw('1 = 0');
            }
        } elseif ($isDean) {
            if ($deanFacultyId) {
                $allocationsQuery->where(function ($sub) use ($deanFacultyId) {
                    $sub->whereHas('department', fn($d) => $d->where('faculty_id', $deanFacultyId))
                        ->orWhereHas('course.department', fn($d) => $d->where('faculty_id', $deanFacultyId));
                });

                if ($request->department_id && $request->department_id !== 'ALL') {
                    $deptId = $request->department_id;
                    $allocationsQuery->where(function ($sub) use ($deptId) {
                        $sub->where('department_id', $deptId)
                            ->orWhereHas('course', fn($c) => $c->where('department_id', $deptId));
                    });
                }
            } else {
                $allocationsQuery->whereRaw('1 = 0');
            }
        } else {
            if ($request->department_id && $request->department_id !== 'ALL') {
                $deptId = $request->department_id;
                $allocationsQuery->where(function ($sub) use ($deptId) {
                    $sub->where('department_id', $deptId)
                        ->orWhereHas('course', fn($c) => $c->where('department_id', $deptId));
                });
            } elseif ($request->faculty_id && $request->faculty_id !== 'ALL') {
                $facultyId = $request->faculty_id;
                $allocationsQuery->where(function ($sub) use ($facultyId) {
                    $sub->whereHas('department', fn($d) => $d->where('faculty_id', $facultyId))
                        ->orWhereHas('course.department', fn($d) => $d->where('faculty_id', $facultyId));
                });
            }
        }

        if ($request->search) {
            $search = $request->search;
            $allocationsQuery->where(function ($sub) use ($search) {
                $sub->whereHas('course', fn($c) => $c->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('staff.user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('staff', fn($s) => $s->where('staff_number', 'like', "%{$search}%"));
            });
        }

        $allocations = $allocationsQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statsBase = clone $allocationsQuery;

        $pendingRequestsQuery = \App\Models\CourseAllocationRequest::query()
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->where('status', 'pending');

        if ($isHod) {
            if ($hodDeptId) {
                $pendingRequestsQuery->where(function ($q) use ($hodDeptId) {
                    $q->where('department_id', $hodDeptId)
                      ->orWhereHas('course', fn($cq) => $cq->where('department_id', $hodDeptId));
                });
            } else {
                $pendingRequestsQuery->whereRaw('1 = 0');
            }
        } elseif ($isDean) {
            if ($deanFacultyId) {
                $pendingRequestsQuery->where(function ($q) use ($deanFacultyId) {
                    $q->whereHas('department', fn($d) => $d->where('faculty_id', $deanFacultyId))
                      ->orWhereHas('course.department', fn($d) => $d->where('faculty_id', $deanFacultyId));
                });
            } else {
                $pendingRequestsQuery->whereRaw('1 = 0');
            }
        }
        $pendingRequestsCount = $pendingRequestsQuery->count();

        $stats = [
            'total_allocations' => (clone $statsBase)->count(),
            'unique_courses' => (clone $statsBase)->distinct('course_id')->count('course_id'),
            'unique_lecturers' => (clone $statsBase)->distinct('staff_id')->count('staff_id'),
            'pending_requests' => $pendingRequestsCount,
        ];

        return Inertia::render('Admin/CourseAllocation/Index', [
            'allocations' => $allocations,
            'stats' => $stats,
            'sessions' => fn() => \App\Services\AcademicCacheService::getSessions(),
            'faculties' => function () use ($isDean, $deanFacultyId) {
                if ($isDean && $deanFacultyId) {
                    return Faculty::with(['departments' => fn($q) => $q->where('is_active', true)->where('is_academic', true)->orderBy('name')])
                        ->where('id', $deanFacultyId)
                        ->get();
                }
                return \App\Services\AcademicCacheService::getFaculties();
            },
            'filters' => $request->only(['session_id', 'department_id', 'search', 'faculty_id']),
            'currentSessionId' => $currentSessionId,
            'courses' => function () use ($isHod, $hodDeptId, $isDean, $deanFacultyId) {
                $q = Course::select('id', 'code', 'title', 'units', 'level', 'semester', 'department_id');
                if ($isHod && $hodDeptId) {
                    $q->where('department_id', $hodDeptId);
                } elseif ($isDean && $deanFacultyId) {
                    $q->whereHas('department', fn($d) => $d->where('faculty_id', $deanFacultyId));
                }
                return $q->orderBy('code')->get();
            },
            'programmes' => fn() => \App\Services\AcademicCacheService::getProgrammes(),
            'lecturers' => function () use ($isHod, $hodDeptId, $isDean, $deanFacultyId) {
                $q = Staff::with(['user:id,name,email', 'department:id,name,code'])
                    ->whereHas('user', function ($uq) {
                        $uq->role('lecturer');
                    });

                if ($isHod && $hodDeptId) {
                    $q->where('department_id', $hodDeptId);
                } elseif ($isDean && $deanFacultyId) {
                    $q->whereHas('department', fn($d) => $d->where('faculty_id', $deanFacultyId));
                }

                return $q->get()->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->user?->name . ' (' . ($s->department?->code ?? 'Dept') . ')',
                        'email' => $s->user?->email,
                        'staff_number' => $s->staff_number,
                    ];
                });
            },
            'isHod' => $isHod,
            'isDean' => $isDean,
        ]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'staff_id' => 'required|exists:staff,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'department_id' => 'nullable|exists:departments,id',
            'program_id' => 'nullable|exists:programmes,id',
        ]);

        // Prevent duplicate allocation for same course in same session to same staff
        $exists = \App\Models\CourseAllocation::where($validated)->exists();
        if ($exists) {
            return back()->with('error', 'This lecturer is already assigned to this course for the selected session.');
        }

        \App\Models\CourseAllocation::create($validated);

        \App\Services\AcademicCacheService::clearTimetableCache();

        return back()->with('success', 'Course assigned successfully.');
    }

    public function destroy($id)
    {
        \App\Models\CourseAllocation::findOrFail($id)->delete();

        \App\Services\AcademicCacheService::clearTimetableCache();

        return back()->with('success', 'Allocation removed successfully.');
    }

    public function import(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|file|extensions:csv,xls,xlsx|max:2048',
        ]);

        try {
            $import = new \App\Imports\CourseAllocationImport;
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            \App\Services\AcademicCacheService::clearTimetableCache();

            $stats = $import->getStats();
            $msg = "Import processed: {$stats['created']} allocations created.";
            if ($stats['duplicates'] > 0) {
                $msg .= " ({$stats['duplicates']} duplicates skipped)";
            }

            if (count($stats['errors']) > 0) {
                return back()->with('warning', $msg . ' Issues found: ' . implode(' • ', array_slice($stats['errors'], 0, 5)));
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Error during import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'course_code',
            'staff_number'
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers); // Header
            fputcsv($file, ['CSC101', 'STF/2026/001']); // Sample
            fputcsv($file, ['MTH202', 'STF/2026/002']); // Sample
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=course_allocation_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
