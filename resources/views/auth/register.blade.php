<x-guest-layout title="Registreer">
    <x-slot:header>
        <span class="section-label section-label-hero">Registreer</span>
        <h1 class="text-4xl font-heading font-bold mt-1">Maak een account</h1>
    </x-slot:header>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label for="first_name">Voornaam</flux:label>
                <flux:input id="first_name" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" />
            </flux:field>

            <flux:field>
                <flux:label for="last_name">Achternaam</flux:label>
                <flux:input id="last_name" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('last_name')" />
            </flux:field>
        </div>

        <!-- Email Address -->
        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </flux:field>

        <!-- Password -->
        <flux:field>
            <flux:label for="password">Wachtwoord</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </flux:field>

        <!-- Confirm Password -->
        <flux:field>
            <flux:label for="password_confirmation">Bevestig wachtwoord</flux:label>
            <flux:input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </flux:field>

        <div class="flex items-start gap-3">
            <input type="checkbox" id="terms" name="terms" value="1" @checked(old('terms')) class="mt-1 rounded border-gray-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)]" />
            <label for="terms" class="text-sm text-[var(--color-text-secondary)]">
                Ik ga akkoord met de <a href="{{ route('legal.terms') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">gebruiksvoorwaarden</a> en het <a href="{{ route('legal.privacy') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">privacybeleid</a>.
            </label>
        </div>
        <x-input-error :messages="$errors->get('terms')" />

        <div class="flex items-center justify-between pt-2">
            <flux:link href="{{ route('login') }}" variant="subtle">
                Al geregistreerd?
            </flux:link>

            <flux:button type="submit" variant="primary">
                Registreer
            </flux:button>
        </div>
    </form>
</x-guest-layout>
