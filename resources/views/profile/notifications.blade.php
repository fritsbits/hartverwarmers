<x-sidebar-layout title="Meldingen" section-label="Profiel" description="Beheer welke e-mailmeldingen je ontvangt.">
    <flux:card>
        <form action="{{ route('profile.notifications.update') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <div>
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Reacties op fiches</p>
                    <flux:field variant="inline">
                        <flux:checkbox
                            name="notify_on_fiche_comments"
                            value="1"
                            :checked="old('notify_on_fiche_comments', $user->notify_on_fiche_comments)"
                        />
                        <flux:label>Stuur me een e-mail wanneer iemand reageert op mijn fiche</flux:label>
                    </flux:field>
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Opslaan</flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</x-sidebar-layout>
