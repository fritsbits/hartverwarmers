@props(['fiche', 'size' => 'md'])

@php
    $colors = config('fiche-icons.colors');
    $color = $colors[($fiche->id ?? 0) % count($colors)];

    $sizeMap = [
        'sm' => ['disc' => 'w-8 h-8', 'icon' => 'w-4 h-4'],
        'md' => ['disc' => 'w-12 h-12', 'icon' => 'w-6 h-6'],
        'lg' => ['disc' => 'w-16 h-16', 'icon' => 'w-8 h-8'],
    ];
    $sizes = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

<div {{ $attributes->merge(['class' => "{$sizes['disc']} rounded-full flex items-center justify-center shrink-0"]) }}
     style="background-color: {{ $color['bg'] }}; color: {{ $color['text'] }}">
    @if($fiche->icon)
        <x-dynamic-component :component="'lucide-' . $fiche->icon" :class="$sizes['icon']" />
    @else
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $sizes['icon'] }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
    @endif
</div>
