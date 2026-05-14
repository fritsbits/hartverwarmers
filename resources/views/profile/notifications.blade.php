<x-sidebar-layout title="Meldingen" section-label="Profiel" description="Kies welke e-mails je wil krijgen, en hoe vaak.">
    <flux:card>
        <form action="{{ route('profile.notifications.update') }}" method="POST">
            @csrf

            <div class="divide-y divide-[var(--color-border-light)]">

                {{-- Comment digests --}}
                <section class="pb-8">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Reacties op fiches</p>
                    <p class="text-sm text-[var(--color-text-secondary)] mb-3">In plaats van een mail per reactie krijg je een overzicht — kies hoe vaak.</p>
                    @php($currentFrequency = old('notification_frequency', $user->notification_frequency ?: 'weekly'))
                    <flux:radio.group
                        name="notification_frequency"
                        class="flex! flex-wrap items-center gap-x-6 gap-y-2 *:data-flux-field:mb-0!"
                    >
                        <flux:radio value="daily" label="Dagelijks" :checked="$currentFrequency === 'daily'" />
                        <flux:radio value="weekly" label="Wekelijks" :checked="$currentFrequency === 'weekly'" />
                        <flux:radio value="never" label="Nooit" :checked="$currentFrequency === 'never'" />
                    </flux:radio.group>
                    @error('notification_frequency')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </section>

                {{-- Kudos milestones --}}
                <section class="py-8">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Kudos en bladwijzers</p>
                    <flux:field variant="inline">
                        <flux:checkbox
                            name="notify_on_kudos_milestones"
                            value="1"
                            :checked="old('notify_on_kudos_milestones', $user->notify_on_kudos_milestones)"
                        />
                        <flux:label>Laat het me weten als iemand mijn fiche bookmarkt — de eerste keer, en bij 10 en 50.</flux:label>
                    </flux:field>
                </section>

                {{-- Maandelijkse nieuwsbrief --}}
                <section class="py-8">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Maandelijkse nieuwsbrief</p>
                    <flux:field variant="inline">
                        <flux:checkbox
                            name="newsletter_subscribed"
                            value="1"
                            :checked="old('newsletter_subscribed', $user->newsletter_unsubscribed_at === null)"
                        />
                        <flux:label>Stuur me elke maand een overzicht — themadagen, nieuwe fiches, en het diamantje van de maand.</flux:label>
                    </flux:field>
                </section>

                {{-- Welkom & inspiratie --}}
                <section class="py-8">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Welkom &amp; inspiratie</p>
                    <flux:field variant="inline">
                        <flux:checkbox
                            name="notify_on_onboarding_emails"
                            value="1"
                            :checked="old('notify_on_onboarding_emails', $user->notify_on_onboarding_emails)"
                        />
                        <flux:label>Stuur me af en toe inspiratie als ik net begin.</flux:label>
                    </flux:field>
                </section>

                <div class="pt-8 flex justify-end">
                    <flux:button type="submit" variant="primary">Opslaan</flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</x-sidebar-layout>
