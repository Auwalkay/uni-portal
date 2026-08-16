<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Http\Request;

class StudentSessionController extends Controller
{
    public function store(Request $request, Student $student)
    {
        if (!auth()->user()->can('edit_students')) {
            abort(403);
        }

        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'level' => 'required|in:100,200,300,400,500',
            'semester' => 'required|string|max:255',
            'status' => 'required|string|max:255',
        ]);

        // Check if session already exists for this student
        $exists = $student->sessions()->where('session_id', $validated['session_id'])->exists();
        if ($exists) {
            return back()->with('error', 'A session history record already exists for the selected academic session.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($student, $validated) {
            if ($validated['status'] === 'active') {
                $student->sessions()->where('status', 'active')->update(['status' => 'completed']);
                $student->update(['current_level' => $validated['level']]);
            }
            $student->sessions()->create($validated);
        });

        return back()->with('success', 'Session record added successfully.');
    }

    public function update(Request $request, Student $student, StudentSession $session)
    {
        if (!auth()->user()->can('edit_students')) {
            abort(403);
        }

        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'level' => 'required|in:100,200,300,400,500',
            'semester' => 'required|string|max:255',
            'status' => 'required|string|max:255',
        ]);

        // Check if session ID changed and if the new one already exists
        if ($session->session_id !== $validated['session_id']) {
            $exists = $student->sessions()
                ->where('session_id', $validated['session_id'])
                ->where('id', '!=', $session->id)
                ->exists();
            if ($exists) {
                return back()->with('error', 'A session history record already exists for the selected academic session.');
            }
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($student, $session, $validated) {
            if ($validated['status'] === 'active') {
                $student->sessions()->where('status', 'active')->where('id', '!=', $session->id)->update(['status' => 'completed']);
                $student->update(['current_level' => $validated['level']]);
            }
            $session->update($validated);
        });

        return back()->with('success', 'Session record updated successfully.');
    }
}
