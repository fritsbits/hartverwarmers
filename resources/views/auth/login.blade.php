<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6 text-center">Inloggen</h1>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-control">
            <x-input-label for="email" :value="__('E-mailadres')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="form-control mt-4">
            <x-input-label for="password" :value="__('Wachtwoord')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Remember Me -->
        <div class="form-control mt-4">
            <label class="label cursor-pointer justify-start gap-2">
                <input id="remember_me" type="checkbox" class="checkbox checkbox-primary checkbox-sm" name="remember">
                <span class="label-text">Onthoud mij</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="link link-hover text-sm" href="{{ route('password.request') }}">
                    Wachtwoord vergeten?
                </a>
            @endif

            <x-primary-button>
                Inloggen
            </x-primary-button>
        </div>

        <div class="divider my-6">of</div>

        <p class="text-center text-sm">
            Nog geen account?
            <a href="{{ route('register') }}" class="link link-primary">Registreer nu</a>
        </p>
    </form>
</x-guest-layout>
