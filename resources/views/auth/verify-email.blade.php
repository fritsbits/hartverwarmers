<x-guest-layout>
    <flux:heading size="xl" class="text-center mb-6">E-mail verifieren</flux:heading>

    <flux:text class="mb-4 text-[var(--color-text-secondary)]">
        Bedankt voor je registratie! Klik op de link die we naar je e-mailadres hebben gestuurd om je account te verifieren. Als je de e-mail niet hebt ontvangen, kunnen we een nieuwe sturen.
    </flux:text>

    @if (session('status') == 'verification-link-sent')
        <flux:callout variant="success" class="mb-4">
            Er is een nieuwe verificatie link naar je e-mailadres gestuurd.
        </flux:callout>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <flux:button type="submit" variant="primary">
                Verstuur opnieuw
            </flux:button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost">
                Uitloggen
            </flux:button>
        </form>
    </div>
</x-guest-layout>
