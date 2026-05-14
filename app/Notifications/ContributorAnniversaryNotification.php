<?php

namespace App\Notifications;

use App\Services\ContributorAnniversary\Payload;
use Illuminate\Notifications\Messages\MailMessage;

class ContributorAnniversaryNotification extends BaseMailNotification
{
    public function __construct(public Payload $payload, public int $year) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->year === 1
            ? "Eén jaar geleden deelde je je eerste fiche, {$notifiable->first_name}"
            : "{$this->year} jaar bijdragen aan Hartverwarmers, {$notifiable->first_name}";

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.anniversary', [
                'notifiable' => $notifiable,
                'payload' => $this->payload,
                'year' => $this->year,
            ]);
    }
}
