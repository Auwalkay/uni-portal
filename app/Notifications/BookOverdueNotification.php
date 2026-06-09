<?php

namespace App\Notifications;

use App\Models\BookLoan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookOverdueNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('URGENT: Library Book Overdue Notice')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is an automated notice that the library book you borrowed is now overdue.')
            ->line('**Book Title:** ' . $book->title)
            ->line('**Author:** ' . $book->author)
            ->line('**Due Date:** ' . $this->loan->due_at->format('M d, Y'))
            ->line('Please return the physical book to the library desk as soon as possible to avoid fines or registration holds.')
            ->action('View My Loans', url('/student/library'))
            ->line('Thank you for your prompt cooperation.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'book_loan_id' => $this->loan->id,
            'book_title' => $this->loan->book->title,
            'due_at' => $this->loan->due_at->toIso8601String(),
            'message' => 'Your copy of "' . $this->loan->book->title . '" is overdue.',
            'action_url' => '/student/library',
        ];
    }
}
