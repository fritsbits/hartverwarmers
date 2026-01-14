<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6 text-center">Wachtwoord resetten</h1>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="form-control">
            <x-input-label for="email" :value="__('E-mailadres')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="form-control mt-4">
            <x-input-label for="password" :value="__('Nieuw wachtwoord')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div class="form-control mt-4">
            <x-input-label for="password_confirmation" :value="__('Bevestig wachtwoord')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                Wachtwoord resetten
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
