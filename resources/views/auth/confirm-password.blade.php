<x-guest-layout title="Wachtwoord bevestigen">
    <x-slot:header>
        <span class="section-label section-label-hero">Bevestig wachtwoord</span>
        <h1 class="text-4xl font-heading font-bold mt-1 mb-4">Beveiligde zone</h1>
        <p class="text-lg text-[var(--color-text-secondary)]">Bevestig je wachtwoord om verder te gaan.</p>
    </x-slot:header>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <flux:field>
            <flux:label for="password">Wachtwoord</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </flux:field>

        <div class="flex justify-end pt-2">
            <flux:button type="submit" variant="primary">
                Bevestig
            </flux:button>
        </div>
    </form>
</x-guest-layout>
