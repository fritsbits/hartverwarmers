@props(['kr', 'range', 'step' => null])

@php
    $value = $kr->metric_key
        ? app(\App\Services\Okr\MetricRegistry::class)->compute($kr->metric_key, $range)
        : new \App\Services\Okr\MetricValue;
    $progress = $kr->target_value && $value->current !== null
        ? min(100, ($value->current / $kr->target_value) * 100)
        : null;
@endphp

<div class="relative p-4 {{ $step !== null ? 'pl-14' : '' }}">
    @if($step !== null)
        <span class="absolute left-4 top-4 inline-flex items-center justify-center w-7 h-7 rounded-full bg-[var(--color-bg-cream)] text-[var(--color-text-secondary)] text-xs font-bold tabular-nums">{{ $step }}</span>
    @endif
    <div class="flex items-baseline justify-between mb-1 gap-3">
        <span class="text-sm font-semibold">{{ $kr->label }}</span>
        <span class="text-sm font-bold text-[var(--color-primary)] tabular-nums whitespace-nowrap">
            {{ $value->display() }}
            @if($value->delta() !== null && $value->delta() !== 0)
                <span class="font-semibold {{ $value->delta() > 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $value->delta() > 0 ? '+' : '' }}{{ $value->delta() }}{{ $value->unit }}
                </span>
            @endif
            @if($kr->target_value !== null)
                <span class="font-normal text-[var(--color-text-tertiary)]">/ {{ $kr->target_value }}{{ $kr->target_unit }}</span>
            @endif
        </span>
    </div>
    @if($progress !== null)
        <div class="h-1 bg-[var(--color-border-light)] rounded-full mb-1">
            <div class="h-1 bg-[var(--color-primary)] rounded-full" style="width: {{ $progress }}%"></div>
        </div>
    @endif
    {{ $slot }}
    @if($value->lowData)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
    @endif
</div>
