<x-guest-layout title="Registreer">
    <x-slot:header>
        <span class="section-label section-label-hero">Registreer</span>
        <h1 class="mt-1">Maak een account</h1>
    </x-slot:header>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true;">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label for="first_name">Voornaam</flux:label>
                <flux:input id="first_name" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" maxlength="255" />
                <flux:error name="first_name" />
            </flux:field>

            <flux:field>
                <flux:label for="last_name">Achternaam</flux:label>
                <flux:input id="last_name" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" maxlength="255" />
                <flux:error name="last_name" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" maxlength="255" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password" class="flex items-center gap-1.5">
                Wachtwoord
                <flux:tooltip>
                    <flux:icon.information-circle variant="micro" class="size-4 text-zinc-400" />
                    <flux:tooltip.content>Kies minstens 8 tekens.</flux:tooltip.content>
                </flux:tooltip>
            </flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label for="password_confirmation">Bevestig wachtwoord</flux:label>
            <flux:input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <flux:error name="password_confirmation" />
        </flux:field>

        <div>
            <flux:checkbox id="terms" name="terms" value="1" checked label="{{ 'Ik ga akkoord met de gebruiksvoorwaarden en het privacybeleid.' }}" />
            <p class="mt-1 ml-7 text-sm text-[var(--color-text-secondary)]">
                Lees de <a href="{{ route('legal.terms') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">gebruiksvoorwaarden</a> en het <a href="{{ route('legal.privacy') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">privacybeleid</a>.
            </p>
            <flux:error name="terms" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <flux:link href="{{ route('login') }}" variant="subtle">
                Al geregistreerd?
            </flux:link>

            <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                <span x-show="!submitting">Registreer</span>
                <span x-show="submitting" x-cloak>Bezig...</span>
            </flux:button>
        </div>
    </form>
</x-guest-layout>
