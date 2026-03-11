<x-guest-layout title="Wachtwoord herstellen">
    <x-slot:header>
        <span class="section-label section-label-hero">Nieuw wachtwoord</span>
        <h1 class="mt-1">Kies een nieuw wachtwoord</h1>
    </x-slot:header>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true;">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </flux:field>

        <flux:field>
            <flux:label for="password">Nieuw wachtwoord</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="new-password" />
            <flux:description>Minstens 8 tekens.</flux:description>
            <x-input-error :messages="$errors->get('password')" />
        </flux:field>

        <flux:field>
            <flux:label for="password_confirmation">Bevestig wachtwoord</flux:label>
            <flux:input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </flux:field>

        <div class="flex items-center justify-end pt-2">
            <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                <span x-show="!submitting">Stel in</span>
                <span x-show="submitting" x-cloak>Bezig...</span>
            </flux:button>
        </div>
    </form>
</x-guest-layout>
