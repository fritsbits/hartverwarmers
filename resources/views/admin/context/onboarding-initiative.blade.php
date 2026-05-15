<div class="grid gap-4">
    @include('admin.initiatives.onboarding-emails')

    <flux:card>
        <flux:heading size="lg" class="font-heading font-bold mb-1">Laatste 5 geverifieerde gebruikers</flux:heading>
        <p class="text-sm text-[var(--color-text-secondary)] mb-4">Per gebruiker: hoe ver kwamen ze in de funnel?</p>

        @if(empty($recentSignupsFunnel))
            <p class="text-sm text-[var(--color-text-secondary)]">Nog geen geverifieerde aanmeldingen.</p>
        @else
            @php
                $columns = [
                    'verified' => 'Verificatie',
                    'returned_7d' => 'Return 7d',
                    'interacted_30d' => 'Interactie 30d',
                    'followup_received' => 'Follow-up',
                ];
            @endphp
            <div class="overflow-x-auto -mx-2">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-[var(--color-text-tertiary)] border-b border-[var(--color-border-light)]">
                            <th class="text-left font-medium px-2 py-2">Naam</th>
                            <th class="text-left font-medium px-2 py-2">Aangemeld</th>
                            @foreach($columns as $key => $label)
                                <th class="text-center font-medium px-2 py-2">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-border-light)]">
                        @foreach($recentSignupsFunnel as $row)
                            <tr>
                                <td class="px-2 py-2 text-[var(--color-text-primary)]">{{ $row['name'] }}</td>
                                <td class="px-2 py-2 text-[var(--color-text-secondary)] tabular-nums">{{ $row['signed_up_at'] }}</td>
                                @foreach($columns as $key => $label)
                                    <td class="px-2 py-2 text-center">
                                        @if($row[$key])
                                            <flux:icon name="check" variant="mini" class="size-4 text-green-600 inline-block" />
                                        @else
                                            <span class="text-[var(--color-text-tertiary)]">–</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </flux:card>

    <x-okr-review-card
        title="Wacht op verificatie >7d"
        subtitle="Interventie-lijst: zijn deze mensen vergeten te verifiëren?"
        :items="$stalledVerifications"
        empty="Geen stalled aanmeldingen."
    />
</div>
