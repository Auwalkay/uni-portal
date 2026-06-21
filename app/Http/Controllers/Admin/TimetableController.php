<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\AcademicCacheService;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $currentSession = Session::current();

        $filters = $request->only(['session_id', 'semester_id', 'department_id', 'level']);

        $query = Timetable::query();

        // Default to current session if no filter
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        } elseif ($currentSession) {
            $query->where('session_id', $currentSession->id);
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $timetables = $query->with(['course', 'department', 'semester', 'session'])
            ->orderBy('department_id')
            ->orderBy('level')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Admin/Timetable/Index', [
            'timetables' => $timetables,
            'sessions' => AcademicCacheService::getSessions(),
            'semesters' => $currentSession ? Semester::where('session_id', $currentSession->id)->get(['id', 'name']) : [],
            'departments' => AcademicCacheService::getAllDepartments(),
            'courses' => AcademicCacheService::getAllCourses(),
            'filters' => $filters,
            'currentSession' => $currentSession,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'required|exists:departments,id',
            'level' => 'required|string',
            'course_id' => 'required|exists:courses,id',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'venue' => 'nullable|string|max:255',
        ]);

        Timetable::create($validated);

        \App\Services\AcademicCacheService::clearTimetableCache();

        return back()->with('success', 'Timetable entry added successfully.');
    }

    public function destroy(Timetable $timetable)
    {
        $timetable->delete();

        \App\Services\AcademicCacheService::clearTimetableCache();

        return back()->with('success', 'Entry removed.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|extensions:csv,xls,xlsx',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\TimetableImport, $request->file('file'));
            \App\Services\AcademicCacheService::clearTimetableCache();
            return back()->with('success', 'Timetable imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="timetable_template.csv"',
        ];

        $columns = ['course_code', 'day', 'start_time', 'end_time', 'venue'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Example row
            fputcsv($file, ['CSC101', 'Monday', '08:00', '10:00', 'Hall A']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
