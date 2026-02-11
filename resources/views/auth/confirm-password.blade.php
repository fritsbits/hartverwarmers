<x-guest-layout>
    <flux:heading size="xl" class="text-center mb-6">Bevestig wachtwoord</flux:heading>

    <flux:text class="mb-4 text-[var(--color-text-secondary)]">
        Dit is een beveiligde zone. Bevestig je wachtwoord om verder te gaan.
    </flux:text>

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
                Bevestigen
            </flux:button>
        </div>
    </form>
</x-guest-layout>
