<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ], [
            'terms.required' => 'Je moet akkoord gaan met de gebruiksvoorwaarden en het privacybeleid.',
            'terms.accepted' => 'Je moet akkoord gaan met de gebruiksvoorwaarden en het privacybeleid.',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false))
            ->with('toast', [
                'heading' => 'Welkom bij de club, '.$user->first_name.'!',
                'text' => 'Fijn dat je erbij bent. Ontdek wat collega\'s delen.',
                'variant' => 'success',
            ]);
    }
}
