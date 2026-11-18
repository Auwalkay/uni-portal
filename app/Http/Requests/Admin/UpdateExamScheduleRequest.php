<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exam_id' => 'nullable|exists:exams,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'semester_id' => 'required|exists:semesters,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'exam_type' => 'nullable|string|in:final,mid_term,cbt,resit',
            'max_capacity' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
        ];
    }
}
