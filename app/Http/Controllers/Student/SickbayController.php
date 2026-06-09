<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SickbayVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SickbayController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        $user = auth()->user();
        
        $visits = SickbayVisit::with(['attendant', 'medicalLog'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Get student next of kin detail to display as Emergency Contact card
        $student = $user->student()
            ->with(['academicDepartment.faculty'])
            ->first();

        return Inertia::render('Student/Sickbay/Index', [
            'visits' => $visits,
            'student' => $student,
        ]);
    }
}
