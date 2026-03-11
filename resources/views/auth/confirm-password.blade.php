<x-guest-layout title="Wachtwoord bevestigen">
    <x-slot:header>
        <span class="section-label section-label-hero">Bevestig wachtwoord</span>
        <h1 class="mt-1 mb-4">Beveiligde zone</h1>
        <p class="text-lg text-[var(--color-text-secondary)]">Bevestig je wachtwoord om verder te gaan.</p>
    </x-slot:header>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true;">
        @csrf

        <flux:field>
            <flux:label for="password">Wachtwoord</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </flux:field>

        <div class="flex justify-end pt-2">
            <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                <span x-show="!submitting">Bevestig</span>
                <span x-show="submitting" x-cloak>Bezig...</span>
            </flux:button>
        </div>
    </form>
</x-guest-layout>
