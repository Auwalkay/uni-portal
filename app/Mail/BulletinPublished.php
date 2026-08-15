<?php

namespace App\Mail;

use App\Models\Bulletin;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulletinPublished extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $bulletin;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Bulletin $bulletin, User $user)
    {
        $this->bulletin = $bulletin;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Announcement: ' . $this->bulletin->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bulletin_published',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
