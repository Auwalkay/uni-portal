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
            ->when($request->department_id, function ($q, $deptId) {
                $q->whereHas('course', fn($c) => $c->where('department_id', $deptId));
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
            'sessions' => Session::latest('start_date')->get(['id', 'name']),
            'faculties' => Faculty::with('departments:id,name,faculty_id')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['session_id', 'department_id', 'search', 'faculty_id']),
            'currentSessionId' => $currentSessionId,
            'courses' => Course::select('id', 'code', 'title', 'department_id')->orderBy('code')->get(),
            'programmes' => \App\Models\Programme::select('id', 'name')->orderBy('name')->get(),
            'lecturers' => Staff::with('user:id,name')
                ->where('staff.is_academic', 1)
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

        return back()->with('success', 'Course assigned successfully.');
    }

    public function destroy($id)
    {
        \App\Models\CourseAllocation::findOrFail($id)->delete();

        return back()->with('success', 'Allocation removed successfully.');
    }

    public function import(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);

        try {
            $import = new \App\Imports\CourseAllocationImport;
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

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
            'staff_email'
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers); // Header
            fputcsv($file, ['CSC101', 'lecturer@university.edu.ng']); // Sample
            fputcsv($file, ['MTH202', 'prof.math@university.edu.ng']); // Sample
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
