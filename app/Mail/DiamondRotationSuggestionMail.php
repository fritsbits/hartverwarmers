<?php

namespace App\Mail;

use App\Models\DiamondRotation;
use App\Models\Fiche;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class DiamondRotationSuggestionMail extends Mailable
{
    /**
     * @param  Collection<int, Fiche>  $candidates  Ordered best-first; the first one is the automatic pick.
     */
    public function __construct(
        public DiamondRotation $rotation,
        public Collection $candidates,
    ) {}

    public function envelope(): Envelope
    {
        $primary = $this->candidates->first();

        return new Envelope(
            subject: "Diamantje van {$this->rotation->monthLabel()}: {$primary->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.diamond-rotation-suggestion',
            with: [
                'rotation' => $this->rotation,
                'primary' => $this->candidates->first(),
                'backups' => $this->candidates->slice(1),
                'chooseUrls' => $this->candidates->mapWithKeys(fn (Fiche $fiche) => [
                    $fiche->id => $this->chooseUrl($fiche),
                ]),
            ],
        );
    }

    /**
     * Signed link valid until the rotation runs on the 1st (06:00 Brussels,
     * stored as UTC month start + 6h) so a late click can't race the award.
     */
    private function chooseUrl(Fiche $fiche): string
    {
        return URL::temporarySignedRoute(
            'diamond-rotation.choose',
            $this->rotation->month->copy()->addHours(6),
            ['rotation' => $this->rotation->id, 'fiche' => $fiche->id],
        );
    }
}
