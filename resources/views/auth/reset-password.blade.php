<x-guest-layout>
    <x-slot:header>
        <span class="section-label section-label-hero">Reset wachtwoord</span>
        <h1 class="text-4xl font-heading font-bold mt-1">Kies een nieuw wachtwoord</h1>
    </x-slot:header>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <flux:field>
            <flux:label for="email">E-mailadres</flux:label>
            <flux:input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </flux:field>

        <!-- Password -->
        <flux:field>
            <flux:label for="password">Nieuw wachtwoord</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </flux:field>

        <!-- Confirm Password -->
        <flux:field>
            <flux:label for="password_confirmation">Bevestig wachtwoord</flux:label>
            <flux:input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </flux:field>

        <div class="flex items-center justify-end pt-2">
            <flux:button type="submit" variant="primary">
                Reset wachtwoord
            </flux:button>
        </div>
    </form>
</x-guest-layout>
