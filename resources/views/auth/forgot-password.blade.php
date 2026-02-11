<x-guest-layout>
    <flux:heading size="xl" class="text-center mb-6">Wachtwoord vergeten</flux:heading>

    <flux:text class="mb-4 text-[var(--color-text-secondary)]">
        Geen probleem. Vul je e-mailadres in en we sturen je een link om je wachtwoord te resetten.
    </flux:text>

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
