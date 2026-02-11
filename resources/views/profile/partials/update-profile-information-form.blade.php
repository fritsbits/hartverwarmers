<section>
    <header>
        <flux:heading size="lg">{{ __('Profile Information') }}</flux:heading>
        <flux:text class="mt-1 text-[var(--color-text-secondary)]">
            {{ __("Update your account's profile information and email address.") }}
        </flux:text>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <flux:field>
            <flux:label for="name">{{ __('Name') }}</flux:label>
            <flux:input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </flux:field>

        <flux:field>
            <flux:label for="email">{{ __('Email') }}</flux:label>
            <flux:input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <flux:text class="text-sm">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="underline text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </flux:text>
                    @endif
                </div>
            @endif
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            @if (session('status') === 'profile-updated')
                <flux:text
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[var(--color-text-secondary)]"
                >{{ __('Saved.') }}</flux:text>
            @endif
        </div>
    </form>
</section>
