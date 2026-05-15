@php
    /** @var \App\Models\Okr\Initiative $initiative */
    /** @var \App\Services\Okr\InitiativeImpactSummary $summary */
    $objective = $initiative->objective;
    $weeksLive = $initiative->started_at?->diffInWeeks(now());
    $deepLink = '?tab='.$objective->slug.'&init='.$initiative->slug;
@endphp

<a href="{{ $deepLink }}" class="block group">
    <flux:card class="hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between gap-4 mb-3">
            <div>
                <flux:heading size="lg" class="font-heading font-bold">{{ $initiative->label }}</flux:heading>
                @if($initiative->started_at)
                    <p class="text-xs text-[var(--color-text-tertiary)] mt-1">
                        Live sinds {{ $initiative->started_at->translatedFormat('j F') }}
                        @if($weeksLive !== null)
                            ({{ $weeksLive }} {{ $weeksLive === 1 ? 'week' : 'weken' }})
                        @endif
                    </p>
                @endif
            </div>
            <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-primary)] whitespace-nowrap">
                {{ $objective->title }} &rarr;
            </span>
        </div>

        @if($initiative->description)
            <p class="text-sm text-[var(--color-text-secondary)] mb-4">{{ $initiative->description }}</p>
        @endif

        <div class="grid gap-2">
            @foreach($summary->krImpacts as $impact)
                <x-okr-kr-impact :impact="$impact" />
            @endforeach
        </div>
    </flux:card>
</a>
