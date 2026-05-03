<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use App\Services\AcademicCacheService;

class AcademicController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $facultyId = $request->input('faculty_id');
        $departmentId = $request->input('department_id');
        $tab = $request->input('tab', 'faculties'); // Default tab

        $faculties = $departments = $programmes = $courses = $units = null;

        if ($tab === 'faculties') {
            $faculties = Faculty::withCount('departments')
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        if ($tab === 'departments') {
            $departments = Department::with('faculty')
                ->withCount(['programmes', 'units'])
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->when($facultyId, function ($q) use ($facultyId) {
                    if ($facultyId === 'NON_ACADEMIC') {
                        $q->whereNull('faculty_id');
                    } else {
                        $q->where('faculty_id', $facultyId);
                    }
                })
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        if ($tab === 'programmes') {
            $programmes = Programme::with('department.faculty')
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($facultyId, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $facultyId)))
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        if ($tab === 'units') {
            $units = Unit::with('department')
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->orderBy('name')
                ->paginate(15, ['*'], 'page')
                ->withQueryString();
        }

        $user = $request->user();
        if ($tab === 'courses') {
            $courses = Course::with('department', 'programme')
                ->when(!$user->can('manage_courses'), function ($q) use ($user) {
                    $q->whereHas('allocations', function ($aq) use ($user) {
                        $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                    });
                })
                ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->when($facultyId, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $facultyId)))
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
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
            'units' => $units ?? $empty,
            'allFaculties' => AcademicCacheService::getAllFaculties(),
            'allDepartments' => AcademicCacheService::getAllDepartments(),
            'allProgrammes' => AcademicCacheService::getAllProgrammes(),
            'filters' => $request->only(['search', 'faculty_id', 'department_id', 'tab']),
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course,unit',
            'id' => 'required|uuid',
            'is_active' => 'required|boolean',
        ]);

        $modelClass = match ($request->type) {
            'faculty' => Faculty::class,
            'department' => Department::class,
            'programme' => Programme::class,
            'course' => Course::class,
            'unit' => Unit::class,
        };

        $item = $modelClass::findOrFail($request->id);
        $item->update(['is_active' => $request->is_active]);

        return back()->with('success', ucfirst($request->type).' status updated.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course,unit',
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
                'faculty_id' => 'nullable|exists:faculties,id',
                'is_academic' => 'required|boolean',
            ]);
            Department::create($data);
        } elseif ($request->type === 'unit') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:units,code',
                'department_id' => 'required|exists:departments,id',
            ]);
            Unit::create($data);
        } elseif ($request->type === 'programme') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'program_type' => 'required|string|in:UG,PG,PHD',
                'department_id' => 'required|exists:departments,id',
                'scholarship_eligible' => 'required|boolean',
            ]);

            Programme::create([
                'name' => $data['name'],
                'type' => $data['program_type'],
                'department_id' => $data['department_id'],
                'scholarship_eligible' => $data['scholarship_eligible'],
            ]);
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

        return back()->with('success', ucfirst($request->type).' created successfully.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course,unit',
            'id' => 'required|uuid',
        ]);

        if ($request->type === 'faculty') {
            $faculty = Faculty::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:faculties,code,'.$faculty->id,
            ]);
            $faculty->update($data);
        } elseif ($request->type === 'department') {
            $department = Department::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:departments,code,'.$department->id,
                'faculty_id' => 'nullable|exists:faculties,id',
                'is_academic' => 'required|boolean',
            ]);
            $department->update($data);
        } elseif ($request->type === 'unit') {
            $unit = Unit::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:units,code,'.$unit->id,
                'department_id' => 'required|exists:departments,id',
            ]);
            $unit->update($data);
        } elseif ($request->type === 'programme') {
            $programme = Programme::findOrFail($request->id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'program_type' => 'required|string|in:UG,PG,PHD',
                'department_id' => 'required|exists:departments,id',
                'scholarship_eligible' => 'required|boolean',
            ]);

            $programme->update([
                'name' => $data['name'],
                'type' => $data['program_type'],
                'department_id' => $data['department_id'],
                'scholarship_eligible' => $data['scholarship_eligible'],
            ]);
        } elseif ($request->type === 'course') {
            $course = Course::findOrFail($request->id);
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:courses,code,'.$course->id,
                'units' => 'required|integer|min:1|max:6',
                'level' => 'required|integer',
                'semester' => 'required|string',
                'department_id' => 'required|exists:departments,id',
                'programme_id' => 'required|exists:programmes,id',
            ]);
            $course->update($data);
        }

        return back()->with('success', ucfirst($request->type).' updated successfully.');
    }
}
