<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6 text-center">Wachtwoord vergeten</h1>

    <div class="mb-4 text-sm text-base-content/70">
        Geen probleem. Vul je e-mailadres in en we sturen je een link om je wachtwoord te resetten.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-control">
            <x-input-label for="email" :value="__('E-mailadres')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="link link-hover text-sm" href="{{ route('login') }}">
                Terug naar inloggen
            </a>

            <x-primary-button>
                Verstuur reset link
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
