<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ApplicantReferee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefereeRecommendationController extends Controller
{
    public function show(string $token)
    {
        $referee = ApplicantReferee::with(['applicant.programme.department.faculty'])
            ->where('token', $token)
            ->firstOrFail();

        return Inertia::render('Public/RefereeRecommendation', [
            'referee' => [
                'id' => $referee->id,
                'name' => $referee->name,
                'email' => $referee->email,
                'phone' => $referee->phone,
                'institution_or_organization' => $referee->institution_or_organization,
                'position_or_rank' => $referee->position_or_rank,
                'relationship' => $referee->relationship,
                'status' => $referee->status,
                'is_submitted' => $referee->isSubmitted(),
                'is_expired' => $referee->isExpired(),
                'submitted_at' => $referee->submitted_at?->toIso8601String(),
                'token' => $referee->token,
            ],
            'applicant' => [
                'full_name' => trim(($referee->applicant?->first_name ?? '') . ' ' . ($referee->applicant?->last_name ?? '')),
                'programme_name' => $referee->applicant?->programme?->name ?? 'Postgraduate Programme',
                'department_name' => $referee->applicant?->programme?->department?->name ?? null,
                'faculty_name' => $referee->applicant?->programme?->department?->faculty?->name ?? null,
                'previous_qualification' => $referee->applicant?->previous_qualification,
                'previous_institution' => $referee->applicant?->previous_institution,
                'research_proposal_title' => $referee->applicant?->research_proposal_title,
            ],
            'recommendationStrengths' => ApplicantReferee::RECOMMENDATION_STRENGTHS,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $referee = ApplicantReferee::where('token', $token)->firstOrFail();

        if ($referee->isSubmitted()) {
            return back()->with('error', 'This recommendation has already been submitted.');
        }

        if ($referee->isExpired()) {
            return back()->with('error', 'This recommendation link has expired.');
        }

        $validated = $request->validate([
            'position_or_rank' => 'nullable|string|max:150',
            'relationship' => 'nullable|string|max:150',
            'academic_capacity_rating' => 'required|integer|min:1|max:5',
            'research_potential_rating' => 'required|integer|min:1|max:5',
            'character_integrity_rating' => 'required|integer|min:1|max:5',
            'communication_rating' => 'required|integer|min:1|max:5',
            'recommendation_strength' => 'required|string|in:strongly_recommend,recommend,recommend_with_reservation,do_not_recommend',
            'comments' => 'required|string|min:10|max:5000',
        ]);

        $referee->update([
            'position_or_rank' => $validated['position_or_rank'] ?? $referee->position_or_rank,
            'relationship' => $validated['relationship'] ?? $referee->relationship,
            'academic_capacity_rating' => $validated['academic_capacity_rating'],
            'research_potential_rating' => $validated['research_potential_rating'],
            'character_integrity_rating' => $validated['character_integrity_rating'],
            'communication_rating' => $validated['communication_rating'],
            'recommendation_strength' => $validated['recommendation_strength'],
            'comments' => $validated['comments'],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Thank you. Your confidential referee recommendation has been submitted to the School of Postgraduate Studies.');
    }
}

