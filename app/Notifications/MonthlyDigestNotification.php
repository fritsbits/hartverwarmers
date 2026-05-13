<?php

namespace App\Notifications;

use App\Services\MonthlyDigest\Payload;
use Illuminate\Notifications\Messages\MailMessage;

class MonthlyDigestNotification extends BaseMailNotification
{
    public function __construct(public Payload $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verse ideeën voor de komende weken')
            ->view('emails.monthly-digest', [
                'notifiable' => $notifiable,
                'payload' => $this->payload,
            ]);
    }
}
