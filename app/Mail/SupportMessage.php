<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [config('mail.support_address')],
            replyTo: [$this->senderEmail],
            subject: "Steunbericht via Hartverwarmers — {$this->senderName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support-message',
        );
    }
}
