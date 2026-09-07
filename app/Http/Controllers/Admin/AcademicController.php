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
    public function faculties(Request $request)
    {
        $search = $request->input('search');

        $faculties = Faculty::withCount('departments')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Faculties/Index', [
            'faculties' => $faculties,
            'stats' => AcademicCacheService::getAcademicStats(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function departments(Request $request)
    {
        $search = $request->input('search');
        $facultyId = $request->input('faculty_id');

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
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Departments/Index', [
            'departments' => $departments,
            'faculties' => AcademicCacheService::getAllFaculties(),
            'stats' => AcademicCacheService::getAcademicStats(),
            'filters' => $request->only(['search', 'faculty_id']),
        ]);
    }

    public function programmes(Request $request)
    {
        $search = $request->input('search');
        $facultyId = $request->input('faculty_id');
        $departmentId = $request->input('department_id');
        $programType = $request->input('program_type');

        $programmes = Programme::with('department.faculty')
            ->withCount('courses')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($facultyId, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $facultyId)))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($programType, fn ($q) => $q->where('type', $programType))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Programmes/Index', [
            'programmes' => $programmes,
            'faculties' => AcademicCacheService::getAllFaculties(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'stats' => AcademicCacheService::getAcademicStats(),
            'filters' => $request->only(['search', 'faculty_id', 'department_id', 'program_type']),
        ]);
    }

    public function showProgrammeDetail(Programme $programme)
    {
        $programme->load(['department.faculty', 'courses']);

        $allCourses = Course::select('id', 'code', 'title', 'units', 'level', 'semester')->orderBy('code')->get();
        $allProgrammes = Programme::where('id', '!=', $programme->id)->orderBy('name')->get();

        return Inertia::render('Admin/Academic/Programmes/Show', [
            'programme' => $programme,
            'allCourses' => $allCourses,
            'allProgrammes' => $allProgrammes,
            'stats' => AcademicCacheService::getAcademicStats(),
        ]);
    }

    public function courses(Request $request)
    {
        $user = $request->user();
        $search = $request->input('search');
        $facultyId = $request->input('faculty_id');
        $departmentId = $request->input('department_id');
        $level = $request->input('level');
        $semester = $request->input('semester');

        $courses = Course::with('department.faculty', 'programme')
            ->when(!$user->can('manage_courses') && !$user->can('view_courses') && !$user->can('manage_academic_sessions'), function ($q) use ($user) {
                $q->whereHas('allocations', function ($aq) use ($user) {
                    $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                });
            })
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($facultyId, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $facultyId)))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->when($semester, fn ($q) => $q->where('semester', (string) $semester))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Courses/Index', [
            'courses' => $courses,
            'faculties' => AcademicCacheService::getAllFaculties(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'programmes' => AcademicCacheService::getAllProgrammes(),
            'stats' => AcademicCacheService::getAcademicStats(),
            'filters' => $request->only(['search', 'faculty_id', 'department_id', 'level', 'semester']),
        ]);
    }

    public function units(Request $request)
    {
        $search = $request->input('search');
        $departmentId = $request->input('department_id');

        $units = Unit::with('department')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Units/Index', [
            'units' => $units,
            'departments' => AcademicCacheService::getAllDepartments(),
            'stats' => AcademicCacheService::getAcademicStats(),
            'filters' => $request->only(['search', 'department_id']),
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'type' => 'required|in:faculty,department,programme,course,unit',
            'id' => 'required|uuid',
            'is_active' => 'required|boolean',
        ]);

        $user = $request->user();
        if ($request->type === 'faculty' && !$user->can('manage_faculties') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'department' && !$user->can('manage_departments') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'unit' && !$user->can('manage_departments') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'programme' && !$user->can('manage_programmes') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'course' && !$user->can('manage_courses') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

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

        $user = $request->user();
        if ($request->type === 'faculty' && !$user->can('manage_faculties') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'department' && !$user->can('manage_departments') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'unit' && !$user->can('manage_departments') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'programme' && !$user->can('manage_programmes') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'course' && !$user->can('manage_courses') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

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
            if ($request->has('code')) {
                $cleaned = str_replace(' ', '', $request->code);
                $cleaned = strtoupper($cleaned);
                if (preg_match('/^([A-Z\-]+)(\d+.*)$/', $cleaned, $matches)) {
                    $formattedCode = $matches[1] . ' ' . $matches[2];
                } else {
                    $formattedCode = $cleaned;
                }
                $request->merge([
                    'code' => $formattedCode
                ]);
            }
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:courses,code',
                'units' => 'required|integer|min:1|max:10',
                'level' => 'required|integer',
                'semester' => 'required|string', // '1' or '2'
                'department_id' => 'required|exists:departments,id',
                'programme_id' => 'nullable|exists:programmes,id',
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

        $user = $request->user();
        if ($request->type === 'faculty' && !$user->can('manage_faculties') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'department' && !$user->can('manage_departments') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'unit' && !$user->can('manage_departments') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'programme' && !$user->can('manage_programmes') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        } elseif ($request->type === 'course' && !$user->can('manage_courses') && !$user->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

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
                'code' => 'required|string|max:20|unique:units,code,'.$unit->id,
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
            if ($request->has('code')) {
                $cleaned = str_replace(' ', '', $request->code);
                $cleaned = strtoupper($cleaned);
                if (preg_match('/^([A-Z\-]+)(\d+.*)$/', $cleaned, $matches)) {
                    $formattedCode = $matches[1] . ' ' . $matches[2];
                } else {
                    $formattedCode = $cleaned;
                }
                $request->merge([
                    'code' => $formattedCode
                ]);
            }
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:courses,code,'.$course->id,
                'units' => 'required|integer|min:1|max:10',
                'level' => 'required|integer',
                'semester' => 'required|string',
                'department_id' => 'required|exists:departments,id',
                'programme_id' => 'nullable|exists:programmes,id',
            ]);
            $course->update($data);
        }

        return back()->with('success', ucfirst($request->type).' updated successfully.');
    }

    public function programmeCourses(Programme $programme)
    {
        if (!auth()->user()->can('manage_programmes') && !auth()->user()->can('manage_courses') && !auth()->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        $courses = $programme->courses()->orderBy('code')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'code' => $c->code,
                'title' => $c->title,
                'units' => $c->units,
                'level' => $c->level,
                'semester' => $c->semester,
                'is_compulsory' => (bool)$c->pivot->is_compulsory,
            ];
        });

        return response()->json($courses);
    }

    public function storeProgrammeCourse(Request $request, Programme $programme)
    {
        if (!$request->user()->can('manage_programmes') && !$request->user()->can('manage_courses') && !$request->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'is_compulsory' => 'required|boolean',
        ]);

        $courseIds = [];
        if (!empty($validated['course_ids'])) {
            $courseIds = $validated['course_ids'];
        } elseif (!empty($validated['course_id'])) {
            $courseIds = [$validated['course_id']];
        }

        if (empty($courseIds)) {
            return response()->json(['message' => 'Please select at least one course.'], 422);
        }

        $existingCourseIds = $programme->courses()->whereIn('course_id', $courseIds)->pluck('course_id')->toArray();
        $newCourseIds = array_diff($courseIds, $existingCourseIds);

        if (empty($newCourseIds)) {
            return response()->json(['message' => 'All selected courses are already added to this programme.'], 422);
        }

        $attachData = [];
        foreach ($newCourseIds as $cid) {
            $attachData[$cid] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'is_compulsory' => $validated['is_compulsory'],
            ];
        }

        $programme->courses()->attach($attachData);

        $addedCount = count($newCourseIds);
        $msg = $addedCount === 1 ? 'Course successfully added to programme.' : "{$addedCount} courses successfully added to programme.";
        return response()->json(['message' => $msg]);
    }

    public function importProgrammeCourses(Request $request, Programme $programme)
    {
        if (!$request->user()->can('manage_programmes') && !$request->user()->can('manage_courses') && !$request->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'source_programme_id' => 'required|exists:programmes,id',
        ]);

        if ($validated['source_programme_id'] === $programme->id) {
            return response()->json(['message' => 'Source programme cannot be the same as the target programme.'], 422);
        }

        $sourceProgramme = Programme::findOrFail($validated['source_programme_id']);
        $existingCourseIds = $programme->courses()->pluck('course_id')->toArray();

        $sourceCourses = $sourceProgramme->courses()->get();
        $importedCount = 0;

        foreach ($sourceCourses as $course) {
            if (!in_array($course->id, $existingCourseIds)) {
                $programme->courses()->attach($course->id, [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'is_compulsory' => $course->pivot->is_compulsory,
                ]);
                $importedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully imported {$importedCount} courses from {$sourceProgramme->name}."
        ]);
    }

    public function destroyProgrammeCourse(Programme $programme, Course $course)
    {
        if (!auth()->user()->can('manage_programmes') && !auth()->user()->can('manage_courses') && !auth()->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        $programme->courses()->detach($course->id);

        return response()->json(['message' => 'Course successfully removed from programme.']);
    }

    public function importProgrammeCoursesFromExcel(Request $request, Programme $programme)
    {
        if (!$request->user()->can('manage_programmes') && !$request->user()->can('manage_courses') && !$request->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|extensions:csv,xls,xlsx|max:4096',
        ]);

        try {
            $import = new \App\Imports\ProgrammeCourseImport($programme);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            \App\Services\AcademicCacheService::clearAll();

            $stats = $import->getStats();
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'message' => "Import processed: {$stats['created']} courses created, {$stats['linked']} courses linked."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadCourseImportTemplate()
    {
        if (!auth()->user()->can('manage_programmes') && !auth()->user()->can('manage_courses') && !auth()->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProgrammeCourseTemplateExport,
            'programme_course_import_template.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function importGlobalCoursesFromExcel(Request $request)
    {
        if (!$request->user()->can('manage_courses') && !$request->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|extensions:csv,xls,xlsx|max:4096',
        ]);

        try {
            $import = new \App\Imports\GlobalCourseImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            \App\Services\AcademicCacheService::clearAll();

            $stats = $import->getStats();
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'message' => "Import processed: {$stats['created']} courses created, {$stats['linked']} courses linked."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadGlobalCourseImportTemplate()
    {
        if (!auth()->user()->can('manage_courses') && !auth()->user()->can('manage_academic_sessions')) {
            abort(403, 'Unauthorized action.');
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\GlobalCourseTemplateExport,
            'global_course_import_template.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportProgrammeCourses(Programme $programme)
    {
        $fileName = \Illuminate\Support\Str::slug($programme->name) . '_curriculum.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProgrammeCurriculumExport($programme),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportCourses(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CoursesCatalogExport($request),
            'course_catalog.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportFaculties(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FacultiesExport($request),
            'faculties_list.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportDepartments(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DepartmentsExport($request),
            'departments_list.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportProgrammes(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProgrammesExport($request),
            'programmes_list.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportUnits(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UnitsExport($request),
            'units_list.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
