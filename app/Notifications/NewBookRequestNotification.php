<?php

namespace App\Notifications;

use App\Models\BookLoan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public BookLoan $loan;

    public function __construct(BookLoan $loan)
    {
        $this->loan = $loan;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $book = $this->loan->book;
        $user = $this->loan->user;

        return (new MailMessage)
            ->subject('New Library Book Borrow Request')
            ->greeting('Hello,')
            ->line("A student has submitted a request to borrow a physical book from the library:")
            ->line("**Book Title:** " . $book->title)
            ->line("**Author:** " . $book->author)
            ->line("**Requester Name:** " . $user->name)
            ->line("**Requester Email:** " . $user->email)
            ->line("**Shelf Location:** " . ($book->shelf_location ?? 'N/A'))
            ->line("**User Notes:** " . ($this->loan->user_notes ?? 'None'))
            ->action('Review Requests', url('/admin/library'))
            ->line('Please log in to the admin panel to approve or reject this request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'book_loan_id' => $this->loan->id,
            'book_title' => $this->loan->book->title,
            'requester_name' => $this->loan->user->name,
            'message' => 'New borrow request for ' . $this->loan->book->title,
            'action_url' => '/admin/library',
        ];
    }
}
