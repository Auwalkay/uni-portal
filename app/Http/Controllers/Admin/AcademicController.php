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
    public function index()
    {
        return Inertia::render('Admin/Academic/Index', [
            'faculties' => Faculty::withCount('departments')->orderBy('name')->get(),
            'departments' => Department::with('faculty')->withCount('programmes')->orderBy('name')->get(),
            'programmes' => Programme::with('department.faculty')->orderBy('name')->get(),
            'courses' => Course::with('department')->orderBy('code')->paginate(50), // Standard pagination for courses
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
            ]);
            $course->update($data);
        }

        return back()->with('success', ucfirst($request->type) . ' updated successfully.');
    }
}
