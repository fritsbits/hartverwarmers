<x-guest-layout>
    <x-slot:header>
        <span class="section-label section-label-hero">Log in</span>
        <h1 class="text-4xl font-heading font-bold mt-1">Welkom terug</h1>
    </x-slot:header>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </flux:field>

        <!-- Password -->
        <flux:field>
            <flux:label for="password">Wachtwoord</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </flux:field>

        <!-- Remember Me -->
        <flux:checkbox id="remember_me" name="remember" label="Onthoud mij" />

        <div class="flex items-center justify-between pt-2">
            @if (Route::has('password.request'))
                <flux:link href="{{ route('password.request') }}" variant="subtle">
                    Wachtwoord vergeten?
                </flux:link>
            @endif

            <flux:button type="submit" variant="primary">
                Log in
            </flux:button>
        </div>

        <flux:separator text="of" class="my-6" />

        <flux:text class="text-center">
            Nog geen account?
            <flux:link href="{{ route('register') }}">Registreer nu</flux:link>
        </flux:text>
    </form>
</x-guest-layout>
