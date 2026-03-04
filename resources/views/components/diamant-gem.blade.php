@props([
    'letter' => '',
    'size' => 'md',
    'active' => true,
    'pronounced' => false,
    'inverted' => false,
])

@php
    $sizeClasses = match($size) {
        'lg' => 'w-12 h-12',
        'md' => 'w-8 h-8',
        'sm' => 'w-7 h-7',
        'xs' => 'w-6 h-6',
        'xxs' => 'w-5 h-5',
        default => 'w-8 h-8',
    };

    $fontSize = match($size) {
        'lg' => 55,
        'md' => 48,
        'sm' => 45,
        'xs' => 43,
        'xxs' => 40,
        default => 48,
    };

    if ($inverted) {
        $fill = '#FFFFFF';
        $textColor = '#48423C';
        $facetColor = '#AEA59C';
    } elseif ($active) {
        $fill = '#E8764B';
        $textColor = '#FFFFFF';
        $facetColor = $pronounced ? 'rgba(255,255,255,0.45)' : 'rgba(255,255,255,0.3)';
    } else {
        $fill = '#F5F0EC';
        $textColor = '#B0A89F';
        $facetColor = $pronounced ? 'rgba(0,0,0,0.12)' : 'rgba(0,0,0,0.08)';
    }
    $facetWidth = $pronounced ? 3 : 2;
    $facetWidthThin = $pronounced ? 2.5 : 1.5;
    $filterId = 'ts-' . uniqid();
@endphp

<svg {{ $attributes->merge(['class' => "$sizeClasses shrink-0 inline-block"]) }} viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    {{-- Gem body --}}
    <polygon points="30,0 70,0 100,35 50,100 0,35" fill="{{ $fill }}" />
    {{-- Facet lines --}}
    <line x1="0" y1="35" x2="100" y2="35" stroke="{{ $facetColor }}" stroke-width="{{ $facetWidth }}" />
    <line x1="30" y1="0" x2="50" y2="35" stroke="{{ $facetColor }}" stroke-width="{{ $facetWidthThin }}" />
    <line x1="70" y1="0" x2="50" y2="35" stroke="{{ $facetColor }}" stroke-width="{{ $facetWidthThin }}" />
    <line x1="25" y1="35" x2="50" y2="100" stroke="{{ $facetColor }}" stroke-width="{{ $facetWidthThin }}" />
    <line x1="75" y1="35" x2="50" y2="100" stroke="{{ $facetColor }}" stroke-width="{{ $facetWidthThin }}" />
    {{-- Letter --}}
    @if($letter)
        @if(!$inverted)
            <defs>
                <filter id="{{ $filterId }}" x="-10%" y="-10%" width="130%" height="130%">
                    <feDropShadow dx="2" dy="2" stdDeviation="0" flood-color="#231E1A" flood-opacity="0.15" />
                </filter>
            </defs>
        @endif
        <text x="50" y="42" text-anchor="middle" dominant-baseline="central"
              fill="{{ $textColor }}" font-family="'Aleo', serif" font-weight="700"
              font-size="{{ $fontSize }}" @if(!$inverted) filter="url(#{{ $filterId }})" @endif>{{ $letter }}</text>
    @endif
</svg>
