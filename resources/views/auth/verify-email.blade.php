<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6 text-center">E-mail verifiëren</h1>

    <div class="mb-4 text-sm text-base-content/70">
        Bedankt voor je registratie! Klik op de link die we naar je e-mailadres hebben gestuurd om je account te verifiëren. Als je de e-mail niet hebt ontvangen, kunnen we een nieuwe sturen.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success mb-4">
            Er is een nieuwe verificatie link naar je e-mailadres gestuurd.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Verstuur opnieuw
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">
                Uitloggen
            </button>
        </form>
    </div>
</x-guest-layout>
