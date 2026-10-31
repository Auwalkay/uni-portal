<?php

namespace App\Mail;

use App\Models\CourseAllocationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseAllocationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $allocationRequest;

    public function __construct(CourseAllocationRequest $allocationRequest)
    {
        $this->allocationRequest = $allocationRequest->loadMissing([
            'staff.user',
            'course',
            'session',
            'reviewer',
        ]);
    }

    public function envelope(): Envelope
    {
        $code = $this->allocationRequest->course->code ?? 'Course';

        return new Envelope(
            subject: "Course Allocation Request Update: {$code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_allocation_rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
