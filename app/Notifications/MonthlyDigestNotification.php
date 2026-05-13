<?php

namespace App\Notifications;

use App\Services\MonthlyDigest\Payload;
use Illuminate\Notifications\Messages\MailMessage;

class MonthlyDigestNotification extends BaseMailNotification
{
    public function __construct(public Payload $payload, public int $cycle = 1) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function idempotencyKey(object $notifiable): string
    {
        return sprintf('digest-%d-cycle-%d', $notifiable->id ?? 0, $this->cycle);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $previewText = $this->previewText();
        $idempotencyKey = $this->idempotencyKey($notifiable);

        return (new MailMessage)
            ->subject('Verse ideeën voor de komende weken')
            ->metadata('preview_text', $previewText)
            ->withSymfonyMessage(function ($message) use ($idempotencyKey): void {
                $message->getHeaders()->addTextHeader('Idempotency-Key', $idempotencyKey);
            })
            ->view('emails.monthly-digest', [
                'notifiable' => $notifiable,
                'payload' => $this->payload,
                'previewText' => $previewText,
            ]);
    }

    private function previewText(): string
    {
        $themes = $this->payload->themes;
        $fiches = $this->payload->newFicheCount;

        if ($themes->isEmpty()) {
            return "{$fiches} nieuwe fiches uit andere woonzorgcentra om uit te putten.";
        }

        $names = $themes->take(3)->map(fn ($o) => $o->theme->title)->all();
        $remaining = $themes->count() - count($names);

        if (count($names) === 1) {
            return "{$names[0]} en {$fiches} nieuwe fiches van collega's.";
        }

        $prefix = match (true) {
            count($names) >= 3 && $remaining > 0 => implode(', ', $names)." en {$remaining} andere thema's",
            count($names) >= 3 => implode(', ', $names),
            default => "{$names[0]} en {$names[1]}",
        };

        return "{$prefix} — plus {$fiches} nieuwe fiches van collega's.";
    }
}
