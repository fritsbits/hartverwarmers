<x-guest-layout title="E-mail verifieren">
    <x-slot:header>
        <span class="section-label section-label-hero">Verifieer e-mail</span>
        <h1 class="mt-1 mb-4">Bijna klaar!</h1>
        <p class="text-lg text-[var(--color-text-secondary)]">We hebben een bevestigingslink naar je inbox gestuurd. Klik op de link en je bent helemaal klaar om aan de slag te gaan.</p>
    </x-slot:header>

    @if (session('status') == 'verification-link-sent')
        <flux:callout variant="success" class="mb-4">
            Er is een nieuwe verificatielink naar je e-mailadres gestuurd.
        </flux:callout>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true;">
            @csrf
            <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                <span x-show="!submitting">Verstuur opnieuw</span>
                <span x-show="submitting" x-cloak>Bezig...</span>
            </flux:button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost">
                Log uit
            </flux:button>
        </form>
    </div>
</x-guest-layout>
