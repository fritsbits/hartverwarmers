@props(['status', 'size' => 'sm'])

@php
    $labels = [
        // Objective statuses
        'on_track' => 'Op koers',
        'at_risk' => 'Bijsturen nodig',
        'paused' => 'Gepauzeerd',
        'done' => 'Afgerond',
        // Initiative statuses
        'soon' => 'Binnenkort',
        'in_progress' => 'Loopt',
        'someday' => 'Ooit',
    ];

    $colors = [
        'on_track' => 'bg-green-100 text-green-800',
        'at_risk' => 'bg-amber-100 text-amber-800',
        'paused' => 'bg-gray-100 text-gray-700',
        'done' => 'bg-blue-100 text-blue-800',
        'soon' => 'bg-amber-50 text-amber-700',
        'in_progress' => 'bg-blue-50 text-blue-700',
        'someday' => 'bg-gray-50 text-gray-600',
    ];

    $label = $labels[$status] ?? $status;
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-full font-medium',
    $color,
    'text-xs px-2 py-0.5' => $size === 'sm',
    'text-sm px-2.5 py-1' => $size === 'md',
]) }}>{{ $label }}</span>
