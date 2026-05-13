<?php

namespace App\Mail;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FicheCommentDigestMail extends Mailable
{
    public function __construct(
        public User $user,
        public Fiche $fiche,
        public array $commentPayloads,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->commentPayloads);
        $subject = $count === 1
            ? "1 nieuwe reactie op je fiche: {$this->fiche->title}"
            : "{$count} nieuwe reacties op je fiche: {$this->fiche->title}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $this->fiche->loadMissing('initiative');

        return new Content(
            markdown: 'emails.fiche-comment-digest',
            with: [
                'user' => $this->user,
                'fiche' => $this->fiche,
                'commentPayloads' => $this->commentPayloads,
                'ficheUrl' => route('fiches.show', [$this->fiche->initiative, $this->fiche]),
            ],
        );
    }
}
