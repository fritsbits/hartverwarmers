<x-sidebar-layout title="Beveiliging" section-label="Profiel" description="Wijzig je wachtwoord en beveiligingsinstellingen.">
    <flux:card>
            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <flux:input type="password" name="current_password" label="Huidig wachtwoord" autocomplete="current-password" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input type="password" name="password" label="Nieuw wachtwoord" autocomplete="new-password" />
                        <flux:input type="password" name="password_confirmation" label="Bevestig wachtwoord" autocomplete="new-password" />
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">Wachtwoord wijzigen</flux:button>
                    </div>
                </div>
            </form>
    </flux:card>
</x-sidebar-layout>
