<section>
    <header>
        <flux:heading size="lg">{{ __('Update Password') }}</flux:heading>
        <flux:text class="mt-1 text-[var(--color-text-secondary)]">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </flux:text>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <flux:field>
            <flux:label for="update_password_current_password">{{ __('Current Password') }}</flux:label>
            <flux:input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </flux:field>

        <flux:field>
            <flux:label for="update_password_password">{{ __('New Password') }}</flux:label>
            <flux:input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </flux:field>

        <flux:field>
            <flux:label for="update_password_password_confirmation">{{ __('Confirm Password') }}</flux:label>
            <flux:input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            @if (session('status') === 'password-updated')
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
