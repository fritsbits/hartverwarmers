<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6 text-center">Bevestig wachtwoord</h1>

    <div class="mb-4 text-sm text-base-content/70">
        Dit is een beveiligde zone. Bevestig je wachtwoord om verder te gaan.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="form-control">
            <x-input-label for="password" :value="__('Wachtwoord')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex justify-end mt-6">
            <x-primary-button>
                Bevestigen
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
