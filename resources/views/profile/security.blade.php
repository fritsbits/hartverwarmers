<x-sidebar-layout title="Beveiliging" section-label="Profiel" description="Wijzig je wachtwoord en beveiligingsinstellingen.">
    <div class="bg-white rounded-2xl p-6 shadow-sm">
        <flux:card>

            @if(session('status') === 'password-updated')
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="mb-6">
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        <span>Wachtwoord succesvol bijgewerkt.</span>
                        <button @click="show = false" class="shrink-0 text-green-600 hover:text-green-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <flux:field>
                        <flux:label>Huidig wachtwoord</flux:label>
                        <flux:input type="password" name="current_password" autocomplete="current-password" />
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" />
                    </flux:field>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Nieuw wachtwoord</flux:label>
                            <flux:input type="password" name="password" autocomplete="new-password" />
                            <x-input-error :messages="$errors->updatePassword->get('password')" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Bevestig wachtwoord</flux:label>
                            <flux:input type="password" name="password_confirmation" autocomplete="new-password" />
                            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
                        </flux:field>
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">Wachtwoord wijzigen</flux:button>
                    </div>
                </div>
            </form>
        </flux:card>
    </div>
</x-sidebar-layout>
