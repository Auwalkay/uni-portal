<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BirthdayWish extends Notification
{
    use Queueable;

    protected $studentName;

    /**
     * Create a new notification instance.
     */
    public function __construct($studentName)
    {
        $this->studentName = $studentName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Happy Birthday from Mewar International University! 🎂')
            ->greeting("Happy Birthday, {$this->studentName}!")
            ->line('On behalf of the entire university community, we wish you a day filled with joy, laughter, and celebration.')
            ->line('May this new year of your life bring you academic success, personal growth, and countless wonderful memories.')
            ->line('Keep shining and making us proud!')
            ->salutation('Best regards, Mewar International University');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Happy Birthday!',
            'message' => "Happy Birthday, {$this->studentName}! Wishing you a fantastic year ahead.",
            'type' => 'birthday',
        ];
    }
}
