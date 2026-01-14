<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6 text-center">Registreren</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="form-control">
            <x-input-label for="name" :value="__('Naam')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <!-- Email Address -->
        <div class="form-control mt-4">
            <x-input-label for="email" :value="__('E-mailadres')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="form-control mt-4">
            <x-input-label for="password" :value="__('Wachtwoord')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div class="form-control mt-4">
            <x-input-label for="password_confirmation" :value="__('Bevestig wachtwoord')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="link link-hover text-sm" href="{{ route('login') }}">
                Al geregistreerd?
            </a>

            <x-primary-button>
                Registreren
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
