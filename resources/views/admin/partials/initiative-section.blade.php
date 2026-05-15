@php
    /** @var \App\Models\Okr\Initiative $initiative */
    /** @var \App\Services\Okr\InitiativeImpactSummary $summary */
    /** @var string|null $contextView */
    $weeksLive = $initiative->started_at !== null ? (int) $initiative->started_at->diffInWeeks(now()) : null;
@endphp

<section id="initiative-{{ $initiative->slug }}" class="scroll-mt-24">
    <flux:card>
        <header class="flex items-start justify-between gap-4 mb-4">
            <div>
                <flux:heading size="lg" class="font-heading font-bold">{{ $initiative->label }}</flux:heading>
                @if($initiative->started_at)
                    <p class="text-xs text-[var(--color-text-tertiary)] mt-1">
                        Live sinds {{ $initiative->started_at->translatedFormat('j F Y') }}
                        @if($weeksLive !== null)
                            ({{ $weeksLive }} {{ $weeksLive === 1 ? 'week' : 'weken' }})
                        @endif
                    </p>
                @endif
            </div>
            <x-okr-status-badge :status="$initiative->status" size="sm" />
        </header>

        @if($initiative->description)
            <p class="text-sm text-[var(--color-text-secondary)] mb-5">{{ $initiative->description }}</p>
        @endif

        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Impact op dit doel</p>
        <div class="grid gap-2 mb-6">
            @foreach($summary->krImpacts as $impact)
                <x-okr-kr-impact :impact="$impact" />
            @endforeach
        </div>

        @if(! empty($contextView ?? null))
            <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Context</p>
            <div class="grid gap-4">
                @include($contextView)
            </div>
        @endif
    </flux:card>
</section>
