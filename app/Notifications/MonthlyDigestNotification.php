<?php

namespace App\Notifications;

use App\Models\ThemeOccurrence;
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
            ->subject($this->subjectLine())
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

    /**
     * Build the subject line from the freshest thing this digest carries, so
     * the inbox never shows the same line twice in a row. An upcoming theme is
     * the strongest, most concrete hook; then the fiche of the month; then the
     * count of freshly shared fiches; and only an empty digest falls back to
     * the evergreen line.
     */
    private function subjectLine(): string
    {
        if ($theme = $this->featuredTheme()) {
            return "{$theme->theme->title} komt eraan — verse ideeën liggen klaar";
        }

        if ($diamond = $this->payload->diamond) {
            return "Fiche van de maand: {$diamond->title}";
        }

        $fiches = $this->payload->newFicheCount;

        if ($fiches === 1) {
            return '1 nieuwe fiche uit een ander woonzorgcentrum';
        }

        if ($fiches > 1) {
            return "{$fiches} nieuwe fiches uit andere woonzorgcentra";
        }

        return 'Verse ideeën voor de komende weken';
    }

    /**
     * Pick the theme to feature in the subject. Awareness-day titles vary wildly
     * in length ("Dierendag" versus "Internationale dag van de vriendschap"), so
     * we feature the shortest upcoming one — it survives mobile truncation and
     * reads punchiest. Ties keep chronological order (the collection is already
     * sorted by start date).
     */
    private function featuredTheme(): ?ThemeOccurrence
    {
        return $this->payload->themes
            ->sortBy(fn (ThemeOccurrence $occurrence): int => mb_strlen($occurrence->theme->title))
            ->first();
    }
}
