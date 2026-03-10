<?php

namespace App\Livewire\Concerns;

use App\Models\Like;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

trait CreatesGuestAccount
{
    public string $guestName = '';

    public string $guestEmail = '';

    public bool $guestTerms = false;

    protected function validateGuestIdentity(): void
    {
        $this->validate([
            'guestName' => 'required|string|max:255',
            'guestEmail' => 'required|email|max:255|unique:users,email',
            'guestTerms' => 'accepted',
        ], [
            'guestName.required' => 'Vul je naam in.',
            'guestEmail.required' => 'Vul je e-mailadres in.',
            'guestEmail.email' => 'Vul een geldig e-mailadres in.',
            'guestEmail.unique' => 'Dit e-mailadres is al in gebruik. Log in om verder te gaan.',
            'guestTerms.accepted' => 'Je moet akkoord gaan met de gebruiksvoorwaarden.',
        ]);
    }

    protected function createGuestUser(): User
    {
        $nameParts = explode(' ', trim($this->guestName));
        $lastName = count($nameParts) > 1 ? array_pop($nameParts) : '';
        $firstName = implode(' ', $nameParts);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->guestEmail,
            'password' => Hash::make(Str::random(32)),
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));

        // Merge session kudos to new user
        Like::whereNull('user_id')
            ->where('session_id', session()->getId())
            ->update(['user_id' => $user->id, 'session_id' => null]);

        Auth::login($user);

        Password::sendResetLink(['email' => $user->email]);

        $this->reset('guestName', 'guestEmail', 'guestTerms');

        return $user;
    }
}
