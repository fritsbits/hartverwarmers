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
