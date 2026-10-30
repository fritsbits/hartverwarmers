<?php

namespace App\Notifications;

use App\Support\MilestoneCall\MilestoneCallContent;
use Illuminate\Notifications\Messages\MailMessage;

class MilestoneCallNotification extends BaseMailNotification
{
    public function __construct(public MilestoneCallContent $content, public int $ownFichesCount = 0) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $remaining = $this->content->remaining();
        $noun = $remaining === 1 ? 'activiteit' : 'activiteiten';

        return (new MailMessage)
            ->subject("Nog {$remaining} {$noun} tot {$this->content->target}")
            ->view('emails.milestone-call', [
                'notifiable' => $notifiable,
                'content' => $this->content,
                'ownFichesCount' => $this->ownFichesCount,
            ]);
    }
}
