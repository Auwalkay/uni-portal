<?php

namespace App\Mail;

use App\Models\CourseAllocationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseAllocationRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $allocationRequest;

    public function __construct(CourseAllocationRequest $allocationRequest)
    {
        $this->allocationRequest = $allocationRequest->loadMissing([
            'staff.user',
            'course',
            'session',
            'department.faculty',
        ]);
    }

    public function envelope(): Envelope
    {
        $code = $this->allocationRequest->course->code ?? 'Course';
        $lecturer = $this->allocationRequest->staff->user->name ?? 'A Lecturer';

        return new Envelope(
            subject: "Course Allocation Request: {$code} by {$lecturer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_allocation_requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
