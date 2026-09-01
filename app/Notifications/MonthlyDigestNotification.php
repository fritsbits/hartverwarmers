<?php

namespace App\Notifications;

use App\Models\ThemeOccurrence;
use App\Services\MonthlyDigest\Payload;
use Illuminate\Notifications\Messages\MailMessage;

class MonthlyDigestNotification extends BaseMailNotification
{
    /**
     * How many themes stay in the running for the subject line. Wide enough
     * that a day's cohorts do not all read the same thing, narrow enough that
     * the weakest candidate never headlines.
     */
    private const SHORTLIST_SIZE = 3;

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
        $featured = $this->featuredTheme();

        $themes = $this->payload->themes
            ->reject(fn (ThemeOccurrence $occurrence): bool => $featured && $occurrence->is($featured))
            ->values();

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
            count($names) >= 3 && $remaining > 0 => implode(', ', $names).' en '.$this->otherThemesPhrase($remaining),
            count($names) >= 3 => implode(', ', $names),
            default => "{$names[0]} en {$names[1]}",
        };

        return "{$prefix} — plus {$fiches} nieuwe fiches van collega's.";
    }

    private function otherThemesPhrase(int $count): string
    {
        return $count === 1 ? '1 ander thema' : "{$count} andere thema's";
    }

    /**
     * Build the subject line from the freshest thing this digest carries. An
     * upcoming theme is the strongest, most concrete hook; then the fiche of
     * the month; then the count of freshly shared fiches; and only an empty
     * digest falls back to the evergreen line.
     */
    private function subjectLine(): string
    {
        if ($theme = $this->featuredTheme()) {
            return $this->themeSubject($theme);
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
     * Word the theme hook to match what is actually behind it. A theme that
     * carries fiches may promise them and say how many; one that carries none
     * asks for an idea instead, so the subject is never a promise the digest
     * cannot keep.
     */
    private function themeSubject(ThemeOccurrence $occurrence): string
    {
        $title = $occurrence->theme->title;
        $fiches = $this->ficheCount($occurrence);

        return match (true) {
            $fiches >= 2 => "{$title} komt eraan — {$fiches} fiches liggen klaar",
            $fiches === 1 => "{$title} komt eraan — 1 fiche ligt klaar",
            default => "{$title} komt eraan — heb jij al een idee?",
        };
    }

    /**
     * Pick the theme to feature in the subject. Editorial weight decides the
     * order: a theme carrying fiches outranks an empty one, more fiches outrank
     * fewer, and an equal pair is settled by whichever comes first.
     *
     * The best few are kept as a shortlist and the recipient's digest cycle
     * picks one of them. The send runs daily against a different cohort, so
     * without that rotation one line would head every mail for as long as its
     * theme sits in the 30-day window — up to a month. Rotating on the cycle
     * spreads the shortlist across each day's recipients and moves a returning
     * reader one slot on, so nobody gets the same line twice in a row.
     */
    private function featuredTheme(): ?ThemeOccurrence
    {
        $shortlist = $this->payload->themes
            ->sortBy([
                fn (ThemeOccurrence $a, ThemeOccurrence $b): int => $this->ficheCount($b) <=> $this->ficheCount($a),
                fn (ThemeOccurrence $a, ThemeOccurrence $b): int => $a->start_date <=> $b->start_date,
            ])
            ->values()
            ->take(self::SHORTLIST_SIZE);

        if ($shortlist->isEmpty()) {
            return null;
        }

        return $shortlist[$this->cycle % $shortlist->count()];
    }

    /**
     * Published fiches behind an occurrence's theme. Composer eager-loads this
     * count; anything that hands over a theme without it is treated as empty.
     */
    private function ficheCount(ThemeOccurrence $occurrence): int
    {
        return (int) ($occurrence->theme->fiches_count ?? 0);
    }
}
