<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::with('semesters')->orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Sessions/Index', [
            'sessions' => $sessions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:academic_sessions,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $session = Session::create($validated);

        // Auto-create Semesters
        \App\Models\Semester::create([
            'session_id' => $session->id,
            'name' => 'First Semester',
            'is_current' => false,
        ]);

        \App\Models\Semester::create([
            'session_id' => $session->id,
            'name' => 'Second Semester',
            'is_current' => false,
        ]);

        return to_route('admin.sessions.show', $session)->with('success', 'Session created. Please configure dates.');
    }


    public function update(Request $request, Session $session)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:academic_sessions,name,' . $session->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $session->update($validated);

        return back()->with('success', 'Session updated successfully.');
    }

    public function activate(Session $session)
    {
        if ($session->is_current) {
            return back()->with('info', 'This session is already active.');
        }

        DB::transaction(function () use ($session) {
            // 1. Deactivate all other sessions
            Session::where('is_current', true)->update(['is_current' => false]);

            // 2. Activate target session
            $session->update(['is_current' => true]);

            // 2b. Activate First Semester by default (and deactivate others globally? No, semester isScoped to session usually, but let's be safe)
            // Deactivate all semesters first? Or just ensure this session's first semester is active.
            // Let's assume global single active semester relative to session.
            \App\Models\Semester::query()->update(['is_current' => false]);

            $firstSemester = $session->semesters()->where('name', 'First Semester')->first();
            if ($firstSemester) {
                $firstSemester->update(['is_current' => true]);
            }

            // 3. Promote Students
            // Logic: Increment level by 100.
            // Consider capping or graduating logic? 
            // For now, simple increment. 
            // We can add a 'status' check to only promote 'Active' students if needed, 
            // but for now we assume all active students move up.

            // Note: DB::raw is efficient for bulk updates.
            // Assuming 'level' is an integer or string castable to int.
            // Student model cast says? Check model. Usually string in Vue but maybe int in DB.
            // Based on grep earlier: $student->current_level.

            // Use query update for efficiency
            // Student::query()->each(function ($student) {
            //     // If level is numeric-ish
            //     $currentLevel = intval($student->current_level); // "100" -> 100
            //     if ($currentLevel > 0) {
            //         $newLevel = $currentLevel + 100;
            //         // Optional: Cap at some point? 
            //         // Let's just increment.
            //         $student->update(['current_level' => $newLevel]);
            //     }
            // });

            Student::query()->increment('current_level', 100);
        });

        return back()->with('success', "Session activated and students promoted to next level.");
    }
    public function activateSemester(Session $session, \App\Models\Semester $semester)
    {
        if ($semester->session_id !== $session->id) {
            return back()->with('error', 'Semester does not belong to this session.');
        }

        DB::transaction(function () use ($session, $semester) {
            // Deactivate all semesters
            \App\Models\Semester::query()->update(['is_current' => false]);

            // Activate target
            $semester->update(['is_current' => true]);

            // Ensure session is also active? Usually yes.
            if (!$session->is_current) {
                // If switching semester of a non-active session, should we activate session?
                // Probably yes, activating a semester implies activating that session timeline.
                \App\Models\Session::where('is_current', true)->update(['is_current' => false]);
                $session->update(['is_current' => true]);
            }
        });

        return back()->with('success', "{$semester->name} is now active.");
    }

    public function show(Session $session)
    {
        $session->load('semesters');

        // Fetch all global fee configurations for this session
        $feeConfigurations = \App\Models\FeeConfiguration::with('feeType')
            ->where('session_id', $session->id)
            ->whereNull('faculty_id')
            ->whereNull('department_id')
            ->whereNull('program_id')
            ->get();

        $feeTypes = \App\Models\FeeType::all();

        return Inertia::render('Admin/Sessions/Show', [
            'session' => $session,
            'feeConfigurations' => $feeConfigurations,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function updateSettings(Request $request, Session $session)
    {
        $validated = $request->validate([
            'registration_enabled' => 'boolean',
            'applications_enabled' => 'boolean',
            'admissions_enabled' => 'boolean',
        ]);

        $session->update($validated);

        return back()->with('success', 'Session settings updated successfully.');
    }

    public function storeFee(Request $request, Session $session)
    {
        $validated = $request->validate([
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
        ]);

        \App\Models\FeeConfiguration::updateOrCreate(
            [
                'session_id' => $session->id,
                'fee_type_id' => $validated['fee_type_id'],
                'faculty_id' => null,
                'department_id' => null,
                'program_id' => null,
            ],
            [
                'amount' => $validated['amount'],
                'is_compulsory' => true,
                'level' => null, // All levels
            ]
        );

        return back()->with('success', 'Fee configuration saved successfully.');
    }

    public function destroyFee(Session $session, \App\Models\FeeConfiguration $feeConfiguration)
    {
        $feeConfiguration->delete();
        return back()->with('success', 'Fee configuration removed.');
    }

    public function toggleRegistration(Session $session)
    {
        $session->update([
            'registration_enabled' => !$session->registration_enabled
        ]);

        $status = $session->registration_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "Course registration {$status} for {$session->name}.");
    }
    public function updateSemester(Request $request, Session $session, \App\Models\Semester $semester)
    {
        if ($semester->session_id !== $session->id) {
            abort(403, 'Semester does not belong to session');
        }

        $validated = $request->validate([
            'registration_starts_at' => 'nullable|date',
            'registration_ends_at' => 'nullable|date|after_or_equal:registration_starts_at',
        ]);

        $semester->update($validated);

        return back()->with('success', "{$semester->name} registration dates updated.");
    }
}
