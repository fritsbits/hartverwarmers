@props(['size' => 12])

@php
    /**
     * De omlijnde ruit die een principe merkt. Eén rendering voor alle maten:
     * de legende op het papier, de chips op een ingeklapte klassiekerrij en de
     * verschuivingen daarbinnen. Zo herkent de lezer het teken terug.
     *
     * De lijndikte schaalt mee zodat ze op elke maat ongeveer 1 px blijft.
     */
    $size = (int) $size;
    $rand = round(130 / $size, 1);
    $facet = round($rand * 0.6, 1);
@endphp

<svg class="principe-gem" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100"
     style="flex: 0 0 {{ $size }}px" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <polygon points="30,0 70,0 100,35 50,100 0,35" fill="none"
             stroke="var(--color-primary)" stroke-width="{{ $rand }}" stroke-linejoin="round" />
    <line x1="0" y1="35" x2="100" y2="35" stroke="var(--color-primary)" stroke-width="{{ $facet }}" />
</svg>
