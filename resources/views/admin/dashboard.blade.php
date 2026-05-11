<x-sidebar-layout title="Beheer" section-label="Beheer">

    <x-slot:header-action>
        <flux:select size="sm" class="w-40" x-data x-on:change="window.location.href = '?tab={{ $tab }}&range=' + $event.target.value">
            <option value="week" {{ $range === 'week' ? 'selected' : '' }}>Laatste week</option>
            <option value="month" {{ $range === 'month' ? 'selected' : '' }}>Laatste maand</option>
            <option value="quarter" {{ $range === 'quarter' ? 'selected' : '' }}>Laatste 3 maanden</option>
            <option value="alltime" {{ $range === 'alltime' ? 'selected' : '' }}>Sinds start</option>
        </flux:select>
    </x-slot:header-action>

    {{-- Tab switcher --}}
    <div x-data="{
        tab: '{{ $tab }}',
        range: '{{ $range }}',
        navigate(val) {
            const validRanges = ['week', 'month', 'quarter', 'alltime'];
            const r = validRanges.includes(this.range) ? this.range : 'month';
            window.location.href = '?tab=' + val + '&range=' + r;
        }
    }" x-init="$watch('tab', val => navigate(val))" class="mb-6">
        <flux:tabs x-model="tab" variant="segmented">
            <flux:tab name="presentatiekwaliteit">Presentatiekwaliteit</flux:tab>
            <flux:tab name="onboarding">Onboarding</flux:tab>
            <flux:tab name="aanmeldingen">Aanmeldingen</flux:tab>
        </flux:tabs>
    </div>

    @if($tab === 'presentatiekwaliteit')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Weekly trend --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-1">Presentatiekwaliteit</flux:heading>
            <p class="text-sm text-[var(--color-text-secondary)] mb-4">
                Gemiddelde score per {{ $range === 'week' ? 'dag' : ($range === 'alltime' ? 'maand' : 'week') }}
            </p>

            @php
                $allSlots = collect($weeklyTrend);
                $trimmedTrend = $allSlots->skipWhile(fn($w) => $w['avg_score'] === null)->values();
                $firstLabel = $trimmedTrend->first()['week_label'] ?? null;
                $lastLabel = $trimmedTrend->last()['week_label'] ?? null;
                $scored = $trimmedTrend->filter(fn($w) => $w['avg_score'] !== null);
                $currentScore = $scored->last()['avg_score'] ?? null;
                $isAlltime = $range === 'alltime';
                $yearMarkers = [];
                if ($isAlltime) {
                    $total = $trimmedTrend->count();
                    foreach ($trimmedTrend as $i => $slot) {
                        if (is_string($slot['week_key']) && str_ends_with($slot['week_key'], '-01')) {
                            $yearMarkers[] = ['year' => substr($slot['week_key'], 0, 4), 'index' => $i];
                        }
                    }
                    $maxMarkers = 8;
                    if (count($yearMarkers) > $maxMarkers) {
                        $step = (int) ceil(count($yearMarkers) / $maxMarkers);
                        $yearMarkers = array_values(array_filter(
                            $yearMarkers,
                            fn($_, $i) => $i % $step === 0,
                            ARRAY_FILTER_USE_BOTH
                        ));
                    }
                }
            @endphp

            @if($scored->isEmpty())
                <p class="text-sm text-[var(--color-text-secondary)]">Nog geen beoordeelde fiches.</p>
            @else
                {{-- Sparkline — only slots from first data point onwards --}}
                <x-chart-tooltip guide>
                    <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-1">
                        @foreach($trimmedTrend as $week)
                            @if($week['avg_score'] !== null)
                                <div
                                    class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                                    style="height: {{ $week['avg_score'] }}%"
                                    data-tip-label="{{ $week['week_label'] }}"
                                    data-tip-value="score {{ $week['avg_score'] }}"
                                ></div>
                            @else
                                <div class="flex-1 rounded-t bg-[var(--color-border-light)] opacity-40 hover:opacity-70 transition-opacity"
                                     style="height: 4px"
                                     data-tip-label="{{ $week['week_label'] }}"
                                     data-tip-value="geen data"></div>
                            @endif
                        @endforeach
                    </div>
                </x-chart-tooltip>
                @if($isAlltime && count($yearMarkers) > 0)
                    <div class="relative h-4 mb-4 text-xs text-[var(--color-text-secondary)]">
                        @foreach($yearMarkers as $marker)
                            <span class="absolute -translate-x-1/2 tabular-nums"
                                  style="left: {{ ($marker['index'] / max(1, $trimmedTrend->count() - 1)) * 100 }}%;">
                                {{ $marker['year'] }}
                            </span>
                        @endforeach
                    </div>
                @elseif($firstLabel && $lastLabel && $firstLabel !== $lastLabel)
                    <div class="flex justify-between text-xs text-[var(--color-text-secondary)] mb-4">
                        <span>{{ $firstLabel }}</span>
                        <span>{{ $lastLabel }}</span>
                    </div>
                @else
                    <div class="mb-4"></div>
                @endif

                {{-- Stats row --}}
                <div class="flex gap-6">
                    @if($currentScore !== null)
                        <div>
                            <div class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $currentScore }}</div>
                            <div class="text-xs text-[var(--color-text-secondary)]">
                                {{ $range === 'week' ? 'meest recent' : ($range === 'alltime' ? 'huidige maand' : 'huidige week') }}
                                @if($trendDelta !== null)
                                    <span class="font-semibold {{ $trendDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                        &nbsp;{{ $trendDelta >= 0 ? '+' : '' }}{{ $trendDelta }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if($globalAvg !== null)
                        <div>
                            <div class="text-2xl font-bold tabular-nums">{{ $globalAvg }}</div>
                            <div class="text-xs text-[var(--color-text-secondary)]">globaal gemiddelde</div>
                        </div>
                    @endif
                </div>
            @endif
        </flux:card>

        {{-- Last 5 fiches --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-1">Laatste 5 fiches</flux:heading>
            <p class="text-sm text-[var(--color-text-secondary)] mb-4">Recentst toegevoegde fiches</p>

            @if($lastFiches->isEmpty())
                <p class="text-sm text-[var(--color-text-secondary)]">Nog geen fiches.</p>
            @else
                <div class="divide-y divide-[var(--color-border-light)]">
                    @foreach($lastFiches as $fiche)
                        @php
                            $score = $fiche->presentation_score;
                            $scoreColor = $score !== null
                                ? ($score >= 70 ? 'text-green-700' : ($score >= 40 ? 'text-amber-600' : 'text-red-600'))
                                : '';
                        @endphp
                        <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
                           class="flex items-center gap-3 py-2 -mx-1 px-1 rounded hover:bg-[var(--color-surface)] transition-colors group">
                            <span class="flex-1 text-sm text-[var(--color-text-secondary)] truncate group-hover:text-[var(--color-text-primary)]">{{ $fiche->title }}</span>
                            @if($score !== null)
                                <span class="text-sm font-bold {{ $scoreColor }} shrink-0 tabular-nums">{{ $score }}</span>
                            @else
                                <span class="text-sm text-[var(--color-text-secondary)] opacity-40 shrink-0">—</span>
                            @endif
                            <span class="text-xs text-[var(--color-text-secondary)] shrink-0 w-28 text-right">{{ $fiche->created_at->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
                @if($lastFiveAvg !== null)
                    <p class="text-xs text-[var(--color-text-secondary)] mt-3">
                        Gem. score laatste 5: <strong>{{ $lastFiveAvg }}</strong>
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
        <p class="text-sm text-[var(--color-text-secondary)] mb-5">Aandeel fiches met overgenomen suggesties · {{ $rangeLabel }}</p>

        @if($withSuggestions === 0)
            <p class="text-sm text-[var(--color-text-secondary)]">Nog geen suggesties gegenereerd.</p>
        @else
            {{-- Summary --}}
            <div class="flex items-baseline gap-3 mb-1">
                <span class="text-3xl font-bold text-[var(--color-primary)] tabular-nums">{{ $adoptionRate }}%</span>
                <span class="text-sm text-[var(--color-text-secondary)]">
                    {{ $withAnyApplied }} van {{ $withSuggestions }} fiches met minstens 1 overgenomen suggestie
                </span>
            </div>
            @if($withSuggestions < 5)
                <p class="text-xs text-[var(--color-text-tertiary)] mb-5">Te weinig data voor betrouwbare conclusies.</p>
            @else
                <div class="mb-5"></div>
            @endif

            {{-- Per veld --}}
            <div class="mb-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-tertiary)] mb-3">Per veld</p>
                <x-chart-tooltip>
                    <div class="space-y-3">
                        @foreach($fieldAdoption as $field => $data)
                            <div class="flex items-center gap-4"
                                 data-tip-label="{{ $data['label'] }}"
                                 data-tip-value="{{ $data['applied'] }} van {{ $data['suggested'] }} ({{ $data['rate'] }}%)">
                                <span class="text-sm font-medium text-[var(--color-text-primary)] w-36 shrink-0">{{ $data['label'] }}</span>
                                <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                                    <div class="h-full bg-[var(--color-primary)] rounded-full" style="width: {{ $data['rate'] }}%"></div>
                                </div>
                                <span class="text-xs font-semibold tabular-nums text-[var(--color-text-secondary)] w-10 text-right shrink-0">{{ $data['rate'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </x-chart-tooltip>
            </div>

            {{-- Per fiche --}}
            @if(!empty($ficheAdoptionDetails))
                <div class="border-t border-[var(--color-border-light)] pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-tertiary)] mb-3">Per fiche</p>
                    <div>
                        @foreach($ficheAdoptionDetails as $detail)
                            <div class="flex items-center gap-3 py-2 border-b border-[var(--color-border-light)] last:border-0">
                                <a href="{{ $detail['url'] }}"
                                   class="flex-1 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] truncate transition-colors min-w-0">
                                    {{ $detail['title'] }}
                                </a>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    @foreach($detail['fields'] as $field => $data)
                                        @if(!$data['suggested'])
                                            <span class="text-xs px-1.5 py-0.5 rounded border border-[var(--color-border-light)] text-[var(--color-text-secondary)] opacity-40"
                                                  title="{{ $data['label'] }}">{{ $data['shortLabel'] }}</span>
                                        @elseif($data['applied'])
                                            <span class="text-xs px-1.5 py-0.5 rounded border border-green-300 bg-green-100 text-green-800"
                                                  title="{{ $data['label'] }} — overgenomen">{{ $data['shortLabel'] }}</span>
                                        @else
                                            <span class="text-xs px-1.5 py-0.5 rounded border border-[var(--color-primary)] text-[var(--color-primary)]"
                                                  title="{{ $data['label'] }} — niet overgenomen">{{ $data['shortLabel'] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                                <span class="text-xs font-medium tabular-nums text-[var(--color-text-secondary)] shrink-0 w-8 text-right">
                                    {{ $detail['adoptedCount'] }}/{{ $detail['suggestedCount'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </flux:card>
    @elseif($tab === 'onboarding')
        @include('admin.partials.onboarding-tab')
    @elseif($tab === 'aanmeldingen')
        @include('admin.partials.aanmeldingen-tab')
    @endif

</x-sidebar-layout>
