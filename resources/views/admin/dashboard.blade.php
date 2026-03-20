<x-sidebar-layout title="Beheer" section-label="Beheer" description="Overzicht van presentatiekwaliteit en suggestie-adoptie.">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Weekly trend --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-1">Presentatiekwaliteit</flux:heading>
            <p class="text-sm text-[var(--color-text-secondary)] mb-4">Gemiddelde score per week (laatste 8 weken)</p>

            @if(collect($weeklyTrend)->filter(fn($w) => $w['avg_score'] !== null)->isEmpty())
                <p class="text-sm text-[var(--color-text-secondary)]">Nog geen beoordeelde fiches.</p>
            @else
                {{-- Sparkline --}}
                <div class="flex items-end gap-1.5 h-16 mb-2">
                    @foreach($weeklyTrend as $week)
                        @if($week['avg_score'] !== null)
                            <div
                                class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                                style="height: {{ $week['avg_score'] }}%"
                                title="{{ $week['week_label'] }}: {{ $week['avg_score'] }}"
                            ></div>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    @endforeach
                </div>
                <div class="flex justify-between text-xs text-[var(--color-text-secondary)] mb-4">
                    <span>8 weken geleden</span>
                    <span>nu</span>
                </div>

                {{-- Stats row --}}
                <div class="flex gap-6">
                    @php
                        $scored = collect($weeklyTrend)->filter(fn($w) => $w['avg_score'] !== null);
                        $currentScore = $scored->last()['avg_score'] ?? null;
                    @endphp
                    @if($currentScore !== null)
                        <div>
                            <div class="text-2xl font-bold text-[var(--color-primary)]">
                                {{ $currentScore }}
                                @if($trendDelta !== null)
                                    <span class="text-sm font-semibold {{ $trendDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $trendDelta >= 0 ? '+' : '' }}{{ $trendDelta }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-[var(--color-text-secondary)]">huidige week gem.</div>
                        </div>
                    @endif
                    @if($globalAvg !== null)
                        <div>
                            <div class="text-2xl font-bold">{{ $globalAvg }}</div>
                            <div class="text-xs text-[var(--color-text-secondary)]">globaal gemiddelde</div>
                        </div>
                    @endif
                </div>
            @endif
        </flux:card>

        {{-- Last 5 fiches --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-1">Laatste 5 fiches</flux:heading>
            <p class="text-sm text-[var(--color-text-secondary)] mb-4">Presentatiescore bij beoordeling</p>

            @if($lastFiches->isEmpty())
                <p class="text-sm text-[var(--color-text-secondary)]">Nog geen beoordeelde fiches.</p>
            @else
                <div class="divide-y divide-[var(--color-border-light)]">
                    @foreach($lastFiches as $fiche)
                        @php
                            $score = $fiche->presentation_score;
                            $pillClass = $score >= 70
                                ? 'bg-green-100 text-green-800'
                                : ($score >= 40 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800');
                        @endphp
                        <div class="flex items-center gap-3 py-2">
                            <span class="flex-1 text-sm text-[var(--color-text-secondary)] truncate">{{ $fiche->title }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $pillClass }}">{{ $score }}</span>
                            <span class="text-xs text-[var(--color-text-secondary)] shrink-0">{{ $fiche->quality_assessed_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
                @if($lastFiveAvg !== null)
                    <p class="text-xs text-[var(--color-text-secondary)] mt-3">
                        Gem. laatste 5: <strong>{{ $lastFiveAvg }}</strong>
                        @if($globalAvg !== null)
                            &nbsp;·&nbsp; Globaal: <strong>{{ $globalAvg }}</strong>
                        @endif
                    </p>
                @endif
            @endif
        </flux:card>

    </div>

    {{-- Suggestion adoption --}}
    <flux:card class="mb-6">
        <flux:heading size="lg" class="font-heading font-bold mb-1">Suggestie-adoptie</flux:heading>
        <p class="text-sm text-[var(--color-text-secondary)] mb-4">Nemen gebruikers de AI-suggesties over?</p>

        @if($withSuggestions === 0)
            <p class="text-sm text-[var(--color-text-secondary)]">Nog geen suggesties gegenereerd.</p>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-[200px_1fr] gap-6">
                <div>
                    <div class="text-4xl font-bold text-[var(--color-primary)]">{{ $adoptionRate }}%</div>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">
                        van de {{ $withSuggestions }} fiches met suggesties<br>
                        heeft minstens 1 suggestie overgenomen
                    </p>
                    <p class="text-xs text-[var(--color-text-secondary)] mt-2">{{ $withAnyApplied }} van {{ $withSuggestions }} fiches</p>
                </div>
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-secondary)]">Adoptie per veld</p>
                    @foreach($fieldAdoption as $field => $data)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-[var(--color-text-secondary)] w-28 shrink-0">{{ $data['label'] }}</span>
                            <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                                <div class="h-full bg-[var(--color-primary)] rounded-full" style="width: {{ $data['rate'] }}%"></div>
                            </div>
                            <span class="text-xs text-[var(--color-text-secondary)] w-8 text-right shrink-0">{{ $data['rate'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:card>

    {{-- Navigation --}}
    <flux:card>
        <flux:heading size="lg" class="font-heading font-bold mb-3">Navigatie</flux:heading>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.fiches.index') }}" class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] bg-[var(--color-surface)] border border-[var(--color-border-light)] rounded-lg px-3 py-1.5 hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors">
                <flux:icon name="document-text" variant="mini" class="size-4" /> Fiches
            </a>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] bg-[var(--color-surface)] border border-[var(--color-border-light)] rounded-lg px-3 py-1.5 hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors">
                <flux:icon name="users" variant="mini" class="size-4" /> Gebruikers
            </a>
            <a href="{{ route('admin.health') }}" class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] bg-[var(--color-surface)] border border-[var(--color-border-light)] rounded-lg px-3 py-1.5 hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors">
                <flux:icon name="heart" variant="mini" class="size-4" /> Gezondheid
            </a>
            <a href="{{ route('admin.features') }}" class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] bg-[var(--color-surface)] border border-[var(--color-border-light)] rounded-lg px-3 py-1.5 hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors">
                <flux:icon name="flag" variant="mini" class="size-4" /> Features
            </a>
            <a href="{{ route('admin.mails') }}" class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] bg-[var(--color-surface)] border border-[var(--color-border-light)] rounded-lg px-3 py-1.5 hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors">
                <flux:icon name="envelope" variant="mini" class="size-4" /> Mails
            </a>
        </div>
    </flux:card>

</x-sidebar-layout>
