@props(['kr', 'range'])

@php
    $value = app(\App\Services\Okr\MetricRegistry::class)->compute($kr->metric_key, $range);
    $progress = $kr->target_value && $value->current !== null
        ? min(100, ($value->current / $kr->target_value) * 100)
        : null;
@endphp

<div class="p-4 bg-[var(--color-bg-cream)] rounded-lg">
    <div class="flex items-baseline justify-between mb-1">
        <span class="text-sm font-semibold">{{ $kr->label }}</span>
        <span class="text-sm font-bold text-[var(--color-primary)] tabular-nums">
            {{ $value->display() }}
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
