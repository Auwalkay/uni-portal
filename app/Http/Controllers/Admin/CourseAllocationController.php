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
        $currentSessionId = Session::current()?->id;
        $sessionId = $request->input('session_id', $currentSessionId);

        $allocations = \App\Models\CourseAllocation::query()
            ->with(['course', 'staff.user', 'session'])
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($request->department_id && $request->department_id !== 'ALL', function ($q, $deptId) {
                $q->whereHas('course', fn($c) => $c->where('department_id', $deptId));
            })
            ->when($request->faculty_id && $request->faculty_id !== 'ALL' && (!$request->department_id || $request->department_id === 'ALL'), function ($q, $facultyId) {
                $q->whereHas('course.department', fn($d) => $d->where('faculty_id', $facultyId));
            })
            ->when($request->search, function ($q, $search) {
                $q->whereHas('course', fn($c) => $c->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('staff.user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/CourseAllocation/Index', [
            'allocations' => $allocations,
            'sessions' => fn() => Session::latest('start_date')->get(['id', 'name']),
            'faculties' => fn() => \App\Services\AcademicCacheService::getFaculties(),
            'filters' => $request->only(['session_id', 'department_id', 'search', 'faculty_id']),
            'currentSessionId' => $currentSessionId,
            'courses' => fn() => Course::select('id', 'code', 'title', 'department_id')->orderBy('code')->get(),
            'programmes' => fn() => \App\Services\AcademicCacheService::getProgrammes(),
            'lecturers' => fn() => Staff::with(['user:id,name', 'department:id,code'])
                ->whereHas('user', function ($q) {
                    $q->role('lecturer');
                })
                ->get()->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->user?->name . ' (' . ($s->department?->code ?? '') . ')',
                    ];
                }),
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
            $msg = "Import processed: {$stats['created']} created, {$stats['skipped']} skipped.";

            if (count($stats['errors']) > 0) {
                return back()->with('warning', $msg . ' Some errors occurred: ' . implode(' | ', array_slice($stats['errors'], 0, 3)));
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
