<?php

namespace App\Services;

use App\Mail\CourseAllocationApprovedMail;
use App\Mail\CourseAllocationRejectedMail;
use App\Mail\CourseAllocationRequestedMail;
use App\Models\CourseAllocationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CourseAllocationNotificationService
{
    /**
     * Notify HOD, Dean, and Admins when a new course request is submitted.
     */
    public static function notifyReviewers(CourseAllocationRequest $request): void
    {
        try {
            $request->loadMissing(['department.faculty', 'staff.user']);
            $department = $request->department;
            $faculty = $department?->faculty;

            $recipientEmails = collect();

            // 1. Department HODs
            if ($department) {
                $hodEmails = User::whereHas('roles', fn($q) => $q->where('name', 'hod'))
                    ->whereHas('staff', fn($q) => $q->where('department_id', $department->id))
                    ->whereNotNull('email')
                    ->pluck('email');
                $recipientEmails = $recipientEmails->merge($hodEmails);
            }

            // 2. Faculty Deans
            if ($faculty) {
                $deanEmails = User::whereHas('roles', fn($q) => $q->where('name', 'dean'))
                    ->whereHas('staff.department', fn($q) => $q->where('faculty_id', $faculty->id))
                    ->whereNotNull('email')
                    ->pluck('email');
                $recipientEmails = $recipientEmails->merge($deanEmails);
            }

            // 3. Portal Admins / Approvers
            $adminEmails = User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'super_admin']))
                ->orWhereHas('permissions', fn($q) => $q->where('name', 'approve_course_allocations'))
                ->whereNotNull('email')
                ->limit(5)
                ->pluck('email');
            $recipientEmails = $recipientEmails->merge($adminEmails);

            // Filter unique, non-empty emails, excluding the requesting lecturer's own email
            $lecturerEmail = $request->staff?->user?->email;
            $recipients = $recipientEmails
                ->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                ->reject(fn($email) => strcasecmp($email, (string) $lecturerEmail) === 0)
                ->unique()
                ->values();

            foreach ($recipients as $email) {
                Mail::to($email)->queue(new CourseAllocationRequestedMail($request));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch course allocation request notification: ' . $e->getMessage(), [
                'request_id' => $request->id,
            ]);
        }
    }

    /**
     * Notify the lecturer when their course request is approved.
     */
    public static function notifyApproved(CourseAllocationRequest $request): void
    {
        try {
            $request->loadMissing(['staff.user', 'course', 'session']);
            $email = $request->staff?->user?->email;

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)->queue(new CourseAllocationApprovedMail($request));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch course allocation approved notification: ' . $e->getMessage(), [
                'request_id' => $request->id,
            ]);
        }
    }

    /**
     * Notify the lecturer when their course request is rejected.
     */
    public static function notifyRejected(CourseAllocationRequest $request): void
    {
        try {
            $request->loadMissing(['staff.user', 'course', 'session']);
            $email = $request->staff?->user?->email;

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)->queue(new CourseAllocationRejectedMail($request));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch course allocation rejected notification: ' . $e->getMessage(), [
                'request_id' => $request->id,
            ]);
        }
    }
}
