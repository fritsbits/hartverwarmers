<x-sidebar-layout title="Meldingen" section-label="Profiel" description="Beheer welke e-mailmeldingen je ontvangt.">
    <flux:card>
        <form action="{{ route('profile.notifications.update') }}" method="POST">
            @csrf

            <div class="space-y-8">

                {{-- Comment digests --}}
                <div>
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Reacties op fiches</p>
                    <p class="text-sm text-[var(--color-text-secondary)] mb-3">Ontvang een overzicht van nieuwe reacties op je fiches.</p>
                    <flux:select name="notification_frequency" class="max-w-xs">
                        <flux:select.option value="daily" :selected="old('notification_frequency', $user->notification_frequency) === 'daily'">Dagelijks</flux:select.option>
                        <flux:select.option value="weekly" :selected="old('notification_frequency', $user->notification_frequency) === 'weekly'">Wekelijks (elke maandag)</flux:select.option>
                        <flux:select.option value="never" :selected="old('notification_frequency', $user->notification_frequency) === 'never'">Nooit</flux:select.option>
                    </flux:select>
                    @error('notification_frequency')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kudos milestones --}}
                <div>
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Kudos en bladwijzers</p>
                    <flux:field variant="inline">
                        <flux:checkbox
                            name="notify_on_kudos_milestones"
                            value="1"
                            :checked="old('notify_on_kudos_milestones', $user->notify_on_kudos_milestones)"
                        />
                        <flux:label>Stuur me een melding wanneer mensen mijn fiche opslaan (eerste keer, 10×, 50×)</flux:label>
                    </flux:field>
                </div>

                {{-- Onboarding --}}
                <div>
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Onboarding</p>
                    <flux:field variant="inline">
                        <flux:checkbox
                            name="notify_on_onboarding_emails"
                            value="1"
                            :checked="old('notify_on_onboarding_emails', $user->notify_on_onboarding_emails)"
                        />
                        <flux:label>Stuur me tips en suggesties na registratie</flux:label>
                    </flux:field>
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Opslaan</flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</x-sidebar-layout>
