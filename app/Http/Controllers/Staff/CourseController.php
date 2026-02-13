<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Session;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $currentSession = Session::current();

        $filters = $request->only(['session_id', 'search']);
        $sessionId = $filters['session_id'] ?? $currentSession?->id;
        if ($sessionId === 'all') {
            $sessionId = null;
        }

        $allocations = CourseAllocation::query()
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('course', function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->with(['course', 'session'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Staff/Course/Index', [
            'allocations' => $allocations,
            'sessions' => Session::orderBy('start_date', 'desc')->get(['id', 'name']),
            'filters' => $filters,
            'currentSessionId' => $currentSession?->id,
        ]);
    }

    public function show(Course $course)
    {
        $user = auth()->user();
        $currentSession = Session::current();

        // 1. Authorization: Ensure user is allocated to this course (and optionally current session)
        // We can loosen the session check if we want them to see history, but for now let's stick to current/active.
        $isAllocated = CourseAllocation::where('course_id', $course->id)
            ->whereHas('staff', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        // Allow admin bypass or strict check? Let's do strict for "Staff" namespace.
        if (!$isAllocated && !$user->hasRole('admin')) {
            abort(403, 'You are not assigned to this course.');
        }

        // 2. Fetch Registered Students
        // Assuming CourseRegistration model links student and course
        $registrations = CourseRegistration::where('course_id', $course->id)
            ->where('session_id', $currentSession->id)
            ->with(['student.user:id,name,email', 'student:id,user_id,matriculation_number,department_id,current_level', 'student.department:id,name'])
            ->latest()
            ->get()
            ->map(fn($reg) => [
                'id' => $reg->id,
                'student_name' => $reg->student->user->name,
                'matriculation_number' => $reg->student->matriculation_number,
                'email' => $reg->student->user->email,
                'department' => $reg->student->department->name,
                'level' => $reg->student->current_level,
                'registered_at' => $reg->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Staff/Course/Show', [
            'course' => $course->only(['id', 'code', 'title', 'unit']),
            'session' => $currentSession->only(['id', 'name']),
            'students' => $registrations,
        ]);
    }
}
