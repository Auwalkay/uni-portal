<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeeReceipt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;
    public $invoice;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($payment, $invoice, $user)
    {
        $this->payment = $payment;
        $this->invoice = $invoice;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt - ' . $this->payment->gateway_reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.fee_receipt',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.receipt', [
            'payment' => $this->payment,
            'invoice' => $this->invoice,
            'user' => $this->user,
        ]);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn() => $pdf->output(), 'Receipt_' . $this->payment->gateway_reference . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
