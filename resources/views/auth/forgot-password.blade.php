<x-guest-layout title="Wachtwoord vergeten">
    <x-slot:header>
        <span class="section-label section-label-hero">Wachtwoord vergeten</span>
        <h1 class="text-4xl font-heading font-bold mt-1 mb-4">Geen probleem</h1>
        <p class="text-lg text-[var(--color-text-secondary)]">Vul je e-mailadres in en we sturen je een link om je wachtwoord te resetten.</p>
    </x-slot:header>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </flux:field>

        <div class="flex items-center justify-between pt-2">
            <flux:link href="{{ route('login') }}" variant="subtle">
                Terug naar inloggen
            </flux:link>

            <flux:button type="submit" variant="primary">
                Verstuur reset link
            </flux:button>
        </div>
    </form>
</x-guest-layout>
