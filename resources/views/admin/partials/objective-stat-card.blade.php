@props(['stat', 'range'])

@php
    /** @var \App\Services\Okr\ObjectiveStat $stat */
    $deepLink = '?tab='.$stat->slug.'&range='.$range;
    $delta = $stat->value->delta();
@endphp

<a href="{{ $deepLink }}" class="block" aria-label="{{ $stat->title }}">
    <flux:card class="overflow-hidden hover:shadow-sm transition-shadow">
        <p class="text-sm text-[var(--color-text-secondary)]">{{ $stat->title }}</p>

        <flux:heading size="xl" class="mt-2 font-heading font-bold tabular-nums">
            {{ $stat->value->display() }}
            @if($delta !== null && $delta !== 0)
                <span class="text-sm font-semibold {{ $delta > 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $delta > 0 ? '+' : '' }}{{ $delta }}{{ $stat->value->unit }}
                </span>
            @endif
        </flux:heading>

        @if(! empty($stat->series))
            <div data-testid="objective-stat-sparkline" class="mt-3">
                <flux:chart :value="$stat->series" class="-mx-6 -mb-6 h-[3rem]">
                    <flux:chart.svg gutter="0">
                        <flux:chart.line field="value" class="text-[var(--color-primary)]" />
                        <flux:chart.area field="value" class="text-[var(--color-primary)]/15" />
                    </flux:chart.svg>
                </flux:chart>
            </div>
        @endif
    </flux:card>
</a>
