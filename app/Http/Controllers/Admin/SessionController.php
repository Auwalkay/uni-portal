<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;
use App\Services\AcademicCacheService;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::with('semesters')->orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Sessions/Index', [
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:academic_sessions,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'type' => 'required|string|in:regular,summer',
        ]);

        $session = Session::create($validated);

        // Auto-create Semesters based on Type
        if ($session->type === 'summer') {
            Semester::create([
                'session_id' => $session->id,
                'name' => 'Summer Semester',
                'is_current' => false,
            ]);
        } else {
            Semester::create([
                'session_id' => $session->id,
                'name' => 'First Semester',
                'is_current' => false,
            ]);

            Semester::create([
                'session_id' => $session->id,
                'name' => 'Second Semester',
                'is_current' => false,
            ]);
        }

        return to_route('admin.sessions.show', $session)->with('success', 'Session created successfully. Please configure fees and activation.');
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

        $this->performActivation($session);

        AcademicCacheService::clearAll();

        return back()->with('success', 'Session activated successfully. You can now manually promote students if this is a new regular session.');
    }

    /**
     * Internal activation logic shared between store, update and activate methods.
     * Returns true if students were promoted.
     */
    private function performActivation(Session $session): void
    {
        DB::transaction(function () use ($session) {
            // 1. Deactivate all other sessions
            Session::where('is_current', true)->where('id', '!=', $session->id)->update(['is_current' => false]);

            // 2. Activate target session
            $session->is_current = true;
            $session->save();

            // 3. Deactivate all semesters
            Semester::where('is_current', true)->update(['is_current' => false]);

            // 4. Activate the first available semester for this session
            $semesterToActivate = $session->semesters()->where('name', 'First Semester')->first()
                ?? $session->semesters()->where('name', 'Summer Semester')->first()
                ?? $session->semesters()->first();

            if ($semesterToActivate) {
                $semesterToActivate->is_current = true;
                $semesterToActivate->save();
            }
        });

        AcademicCacheService::clearAll();
    }

    public function promoteStudents(Session $session)
    {
        if (!$session->is_current) {
            return back()->with('error', 'Only the current active session can be used for student promotion.');
        }

        if ($session->type !== 'regular') {
            return back()->with('error', 'Promotion can only be triggered for Regular academic sessions.');
        }

        \App\Jobs\Academic\PromoteStudentsJob::dispatch($session->id);

        return back()->with('success', 'Student promotion process has been queued and is running in the background.');
    }

    public function activateSemester(Session $session, Semester $semester)
    {
        if ($semester->session_id !== $session->id) {
            return back()->with('error', 'Semester does not belong to this session.');
        }

        DB::transaction(function () use ($session, $semester) {
            // Deactivate all semesters
            Semester::query()->update(['is_current' => false]);

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

        AcademicCacheService::clearAll();

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
            'registration_enabled' => !$session->registration_enabled,
        ]);

        $status = $session->registration_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Course registration {$status} for {$session->name}.");
    }

    public function updateSemester(Request $request, Session $session, Semester $semester)
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
