@props(['stat', 'range'])

@php
    /** @var \App\Services\Okr\ObjectiveStat $stat */
    $deepLink = '?tab='.$stat->slug.'&range='.$range;
    $delta = $stat->value->delta();
    $valueColor = \App\Services\Okr\KrHealth::colorClass($stat->value->current, $stat->target, $stat->metricKey);
@endphp

<a href="{{ $deepLink }}" class="block" aria-label="{{ $stat->title }}">
    <flux:card class="overflow-hidden hover:shadow-sm transition-shadow">
        <p class="text-sm text-[var(--color-text-secondary)]">{{ $stat->title }}</p>

        <flux:heading size="xl" class="mt-2 font-heading font-bold tabular-nums {{ $valueColor }}">
            {{ $stat->value->display() }}
            @if($delta !== null && $delta !== 0)
                <span class="text-sm font-semibold {{ $delta > 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $delta > 0 ? '+' : '' }}{{ $delta }}{{ $stat->value->unit }}
                </span>
            @endif
        </flux:heading>

        <p class="text-[11px] tabular-nums text-[var(--color-text-tertiary)] mt-1">
            @if($stat->value->current === null)
                Nog niet gemeten
            @elseif($stat->target !== null)
                <span class="uppercase tracking-wide">Doel</span> {{ $stat->target }}{{ $stat->value->unit }}
            @else
                Nog geen doel ingesteld
            @endif
        </p>

        @if($stat->value->current !== null && $stat->target !== null && $stat->target > 0)
            @php $progress = min(100, ($stat->value->current / $stat->target) * 100); @endphp
            <div data-testid="objective-stat-progress" class="h-1.5 bg-[var(--color-border-light)] rounded-full mt-3" role="img" aria-label="{{ round($progress) }}% van het doel">
                <div class="h-1.5 {{ \App\Services\Okr\KrHealth::barClass($stat->value->current, $stat->target, $stat->metricKey) }} rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        @endif
    </flux:card>
</a>
