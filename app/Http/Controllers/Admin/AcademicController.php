<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $facultyId = $request->input('faculty_id');
        $departmentId = $request->input('department_id');
        $tab = $request->input('tab', 'faculties'); // Default tab

        $faculties = $departments = $programmes = $courses = null;

        if ($tab === 'faculties') {
            $faculties = Faculty::withCount('departments')
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        if ($tab === 'departments') {
            $departments = Department::with('faculty')
                ->withCount('programmes')
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->when($facultyId, fn($q) => $q->where('faculty_id', $facultyId))
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        if ($tab === 'programmes') {
            $programmes = Programme::with('department.faculty')
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($facultyId, fn($q) => $q->whereHas('department', fn($d) => $d->where('faculty_id', $facultyId)))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        if ($tab === 'courses') {
            $courses = Course::with('department', 'programme')
                ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->when($facultyId, fn($q) => $q->whereHas('department', fn($d) => $d->where('faculty_id', $facultyId)))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->orderBy('code')
                ->paginate(20, ['*'], 'page')
                ->withQueryString();
        }

        // Helper to return empty pagination result if null
        $empty = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);

        return Inertia::render('Admin/Academic/Index', [
            'faculties' => $faculties ?? $empty,
            'departments' => $departments ?? $empty,
            'programmes' => $programmes ?? $empty,
            'courses' => $courses ?? $empty,
            'allFaculties' => Faculty::orderBy('name')->get(),
            'allDepartments' => Department::orderBy('name')->get(),
            'allProgrammes' => Programme::orderBy('name')->get(),
            'filters' => $request->only(['search', 'faculty_id', 'department_id', 'tab']),
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course',
            'id' => 'required|uuid',
            'is_active' => 'required|boolean'
        ]);

        $modelClass = match ($request->type) {
            'faculty' => Faculty::class,
            'department' => Department::class,
            'programme' => Programme::class,
            'course' => Course::class,
        };

        $item = $modelClass::findOrFail($request->id);
        $item->update(['is_active' => $request->is_active]);

        return back()->with('success', ucfirst($request->type) . ' status updated.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course',
        ]);

        if ($request->type === 'faculty') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:faculties,code',
            ]);
            Faculty::create($data);
        } elseif ($request->type === 'department') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:departments,code',
                'faculty_id' => 'required|exists:faculties,id',
            ]);
            Department::create($data);
        } elseif ($request->type === 'programme') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:UG,PG,PHD',
                'department_id' => 'required|exists:departments,id',
            ]);
            Programme::create($data);
        } elseif ($request->type === 'course') {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:courses,code',
                'units' => 'required|integer|min:1|max:6',
                'level' => 'required|integer',
                'semester' => 'required|string', // '1' or '2'
                'department_id' => 'required|exists:departments,id',
                'programme_id' => 'required|exists:programmes,id',
            ]);
            Course::create($data);
        }

        return back()->with('success', ucfirst($request->type) . ' created successfully.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course',
            'id' => 'required|uuid'
        ]);

        if ($request->type === 'faculty') {
            $faculty = Faculty::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:faculties,code,' . $faculty->id,
            ]);
            $faculty->update($data);
        } elseif ($request->type === 'department') {
            $department = Department::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
                'faculty_id' => 'required|exists:faculties,id',
            ]);
            $department->update($data);
        } elseif ($request->type === 'programme') {
            $programme = Programme::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:UG,PG,PHD',
                'department_id' => 'required|exists:departments,id',
            ]);
            $programme->update($data);
        } elseif ($request->type === 'course') {
            $course = Course::findOrFail($request->id);
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:courses,code,' . $course->id,
                'units' => 'required|integer|min:1|max:6',
                'level' => 'required|integer',
                'semester' => 'required|string',
                'department_id' => 'required|exists:departments,id',
                'programme_id' => 'required|exists:programmes,id',
            ]);
            $course->update($data);
        }

        return back()->with('success', ucfirst($request->type) . ' updated successfully.');
    }
}
