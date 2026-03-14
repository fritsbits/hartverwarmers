<?php

namespace App\Livewire;

use App\Mail\SupportMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SupportContactForm extends Component
{
    #[Validate('required', message: ['required' => 'Vul je naam in.'])]
    public string $name = '';

    #[Validate('required|email', message: ['required' => 'Vul je e-mailadres in.', 'email' => 'Vul een geldig e-mailadres in.'])]
    public string $email = '';

    #[Validate('required|max:2000', message: ['required' => 'Schrijf een kort bericht.', 'max' => 'Je bericht mag maximaal 2000 tekens bevatten.'])]
    public string $message = '';

    public bool $sent = false;

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
        ));

        $this->sent = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.support-contact-form');
    }
}
