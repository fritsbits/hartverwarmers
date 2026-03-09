<x-guest-layout title="E-mail verifiëren">
    <x-slot:header>
        <span class="section-label section-label-hero">Verifieer e-mail</span>
        <h1 class="text-4xl font-heading font-bold mt-1 mb-4">Nog één stap</h1>
        <p class="text-lg text-[var(--color-text-secondary)]">Klik op de link die we naar je e-mailadres hebben gestuurd om je account te verifieren. Als je de e-mail niet hebt ontvangen, kunnen we een nieuwe sturen.</p>
    </x-slot:header>

    @if (session('status') == 'verification-link-sent')
        <flux:callout variant="success" class="mb-4">
            Er is een nieuwe verificatie link naar je e-mailadres gestuurd.
        </flux:callout>
    @endif

    <div class="flex items-center justify-between">
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
