<section class="space-y-6">
    <header>
        <flux:heading size="lg">{{ __('Delete Account') }}</flux:heading>
        <flux:text class="mt-1 text-[var(--color-text-secondary)]">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </flux:text>
    </header>

    <flux:button
        variant="danger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</flux:button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <flux:heading size="lg">
                {{ __('Are you sure you want to delete your account?') }}
            </flux:heading>

            <flux:text class="mt-1 text-[var(--color-text-secondary)]">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </flux:text>

            <div class="mt-6">
                <flux:field>
                    <flux:label for="password" class="sr-only">{{ __('Password') }}</flux:label>
                    <flux:input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="{{ __('Password') }}"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('password')" />
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button type="submit" variant="danger">
                    {{ __('Delete Account') }}
                </flux:button>
            </div>
        </form>
    </x-modal>
</section>
