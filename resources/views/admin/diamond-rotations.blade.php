<x-sidebar-layout title="Diamantje van de maand" section-label="Beheer" description="Volg en stuur de maandelijkse diamantje-wissel bij.">

    <div class="space-y-6">

        {{-- Deze maand --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-1">Deze maand · {{ now()->locale('nl_BE')->isoFormat('MMMM YYYY') }}</flux:heading>

            @if ($currentDiamond)
                <div class="flex items-center gap-2 mt-3">
                    <x-diamant-gem size="xxs" :pronounced="true" />
                    <a href="{{ route('fiches.show', [$currentDiamond->initiative, $currentDiamond]) }}" class="font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">{{ $currentDiamond->title }}</a>
                    <span class="text-sm text-[var(--color-text-secondary)]">door {{ $currentDiamond->user->full_name }}</span>
                </div>
                <p class="text-sm text-[var(--color-text-secondary)] mt-2">
                    Toegekend op {{ $currentDiamond->diamond_awarded_at?->locale('nl_BE')->isoFormat('D MMMM YYYY') ?? 'onbekende datum' }}
                    @if ($currentRotation?->isAwarded())
                        · {{ $currentRotation->chosen_via === 'admin' ? 'jouw keuze uit de suggestiemail' : 'automatische wissel' }}
                    @else
                        · handmatig toegekend (buiten de maandwissel om)
                    @endif
                </p>
            @else
                <p class="text-sm text-[var(--color-text-secondary)] mt-2">Er is nog geen enkel diamantje toegekend.</p>
            @endif
        </flux:card>

        {{-- Volgende maand --}}
        <flux:card>
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                <div>
                    <flux:heading size="lg" class="font-heading font-bold mb-1">Volgende maand · {{ $nextMonth->locale('nl_BE')->isoFormat('MMMM YYYY') }}</flux:heading>
                    @if ($nextRotation)
                        <p class="text-sm text-[var(--color-text-secondary)]">
                            @if ($nextRotation->suggestion_sent_at)
                                Suggestiemail verstuurd op {{ $nextRotation->suggestion_sent_at->locale('nl_BE')->isoFormat('D MMMM [om] HH:mm') }}.
                            @else
                                Keuze klaargezet; de suggestiemail vertrekt op de 27e om 09:00.
                            @endif
                            De wissel gebeurt automatisch op 1 {{ $nextMonth->locale('nl_BE')->isoFormat('MMMM') }} om 06:00.
                        </p>
                    @else
                        <p class="text-sm text-[var(--color-text-secondary)]">Nog niets klaargezet — de suggestiemail vertrekt automatisch op de 27e om 09:00, de wissel volgt op de 1e om 06:00.</p>
                    @endif
                </div>
                <form action="{{ route('admin.diamond-rotations.send-suggestion') }}" method="POST" class="shrink-0">
                    @csrf
                    <flux:button variant="ghost" type="submit" size="sm">Verstuur suggestiemail nu</flux:button>
                </form>
            </div>

            @if ($nextRotation?->fiche)
                <div class="rounded-lg bg-[var(--color-bg-accent-light)] border border-[var(--color-border-light)] px-4 py-3 mb-4">
                    <div class="flex items-center gap-2">
                        <x-diamant-gem size="xxs" :pronounced="true" />
                        <a href="{{ route('fiches.show', [$nextRotation->fiche->initiative, $nextRotation->fiche]) }}" class="font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">{{ $nextRotation->fiche->title }}</a>
                        @if ($nextRotation->chosen_via === 'admin')
                            <flux:badge size="sm" color="green" inset="top bottom">Jouw keuze</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" inset="top bottom">Automatische suggestie</flux:badge>
                        @endif
                    </div>
                </div>
            @endif

            @if ($candidates->isEmpty())
                <p class="text-sm text-red-600">Geen kandidaten: elke gepubliceerde fiche heeft al een diamantje. Zonder kandidaat kan de wissel niet doorgaan en zou de maandelijkse update twee keer hetzelfde diamantje tonen.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-[var(--color-text-tertiary)] border-b border-[var(--color-border-light)]">
                                <th class="py-2 pr-4 font-medium">Fiche</th>
                                <th class="py-2 pr-4 font-medium">Score</th>
                                <th class="py-2 pr-4 font-medium">Kudos</th>
                                <th class="py-2 pr-4 font-medium">Reacties</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border-light)]">
                            @foreach ($candidates as $candidate)
                                <tr>
                                    <td class="py-2.5 pr-4">
                                        <a href="{{ route('fiches.show', [$candidate->initiative, $candidate]) }}" class="text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">{{ $candidate->title }}</a>
                                        <span class="text-[var(--color-text-tertiary)]">· {{ $candidate->user->full_name }}</span>
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        @if (is_null($candidate->quality_score))
                                            <span class="text-[var(--color-text-tertiary)]">—</span>
                                        @else
                                            <span class="{{ $candidate->quality_score >= 70 ? 'text-green-700' : ($candidate->quality_score >= 40 ? 'text-amber-600' : 'text-red-600') }}">{{ $candidate->quality_score }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 pr-4">{{ $candidate->likes_count }}</td>
                                    <td class="py-2.5 pr-4">{{ $candidate->comments_count }}</td>
                                    <td class="py-2.5 text-right">
                                        @if ($nextRotation?->fiche_id === $candidate->id)
                                            <span class="text-xs text-[var(--color-text-tertiary)]">Geplande keuze</span>
                                        @else
                                            <form action="{{ route('admin.diamond-rotations.choose') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="fiche_id" value="{{ $candidate->id }}">
                                                <flux:button variant="ghost" type="submit" size="sm">Kies deze</flux:button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>

        {{-- Historiek --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-4">Historiek</flux:heading>

            @if ($history->isEmpty())
                <p class="text-sm text-[var(--color-text-secondary)]">Nog geen automatische wissels gebeurd.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-[var(--color-text-tertiary)] border-b border-[var(--color-border-light)]">
                                <th class="py-2 pr-4 font-medium">Maand</th>
                                <th class="py-2 pr-4 font-medium">Fiche</th>
                                <th class="py-2 pr-4 font-medium">Keuze</th>
                                <th class="py-2 font-medium">Toegekend op</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border-light)]">
                            @foreach ($history as $rotation)
                                <tr>
                                    <td class="py-2.5 pr-4 whitespace-nowrap">{{ $rotation->monthLabel() }}</td>
                                    <td class="py-2.5 pr-4">
                                        @if ($rotation->fiche)
                                            <a href="{{ route('fiches.show', [$rotation->fiche->initiative, $rotation->fiche]) }}" class="text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">{{ $rotation->fiche->title }}</a>
                                        @else
                                            <span class="text-[var(--color-text-tertiary)]">Fiche verwijderd</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 pr-4">{{ $rotation->chosen_via === 'admin' ? 'Zelf gekozen' : 'Automatisch' }}</td>
                                    <td class="py-2.5 whitespace-nowrap">{{ $rotation->awarded_at->locale('nl_BE')->isoFormat('D MMM YYYY, HH:mm') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>

    </div>

</x-sidebar-layout>
