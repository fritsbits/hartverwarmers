<?php

namespace App\Livewire;

use App\Mail\SupportMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SupportContactForm extends Component
{
    /** @var array<string, string> */
    public const REASONS = [
        'feedback' => 'Idee of feedback',
        'vraag' => 'Vraag of probleem',
        'samenwerking' => 'Samenwerking of steun',
        'anders' => 'Iets anders',
    ];

    #[Validate('required|in:feedback,vraag,samenwerking,anders', message: ['required' => 'Kies waarover je bericht gaat.', 'in' => 'Kies een geldige reden.'])]
    public string $reason = '';

    #[Validate('required', message: ['required' => 'Vul je naam in.'])]
    public string $name = '';

    #[Validate('required|email', message: ['required' => 'Vul je e-mailadres in.', 'email' => 'Vul een geldig e-mailadres in.'])]
    public string $email = '';

    #[Validate('required|max:2000', message: ['required' => 'Schrijf een kort bericht.', 'max' => 'Je bericht mag maximaal 2000 tekens bevatten.'])]
    public string $message = '';

    public bool $sent = false;

    public function mount(?string $reason = null): void
    {
        // Livewire pre-assigns a matching mount param to $this->reason, so a valid
        // slug is already set here — we only need to discard an unknown value.
        if ($reason !== null && ! array_key_exists($reason, self::REASONS)) {
            $this->reason = '';
        }
    }

    public function send(): void
    {
        $key = 'support-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('throttle', 'Je hebt te veel berichten verstuurd. Probeer het later opnieuw.');

            return;
        }

        $this->validate();

        RateLimiter::hit($key, 600);

        Mail::queue(new SupportMessage(
            senderName: $this->name,
            senderEmail: $this->email,
            senderMessage: $this->message,
            reasonLabel: self::REASONS[$this->reason],
        ));

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.support-contact-form');
    }
}
