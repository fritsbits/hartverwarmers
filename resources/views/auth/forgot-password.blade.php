<x-guest-layout title="Wachtwoord vergeten">
    <x-slot:header>
        <span class="section-label section-label-hero">Wachtwoord vergeten</span>
        <h1 class="mt-1 mb-4">Geen probleem</h1>
        <p class="text-lg text-[var(--color-text-secondary)]">Vul je e-mailadres in en we sturen je een mail om je wachtwoord te herstellen.</p>
    </x-slot:header>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true;">
        @csrf

        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </flux:field>

        <div class="flex items-center justify-between pt-2">
            <flux:link href="{{ route('login') }}" variant="subtle">
                Terug naar inloggen
            </flux:link>

            <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                <span x-show="!submitting">Stuur herstelmail</span>
                <span x-show="submitting" x-cloak>Bezig...</span>
            </flux:button>
        </div>
    </form>
</x-guest-layout>
