@props(['date'])

@php
    $moment = \App\Support\PostedMoment::for($date);
@endphp

<span {{ $attributes->merge(['class' => $moment->isFresh ? 'fiche-date-fresh' : 'fiche-date-quiet']) }}
      title="Gepost op {{ $date->locale('nl_BE')->translatedFormat('j F Y') }}">
    @if($moment->isFresh)
        <span class="fiche-date-dot"></span>
    @endif
    {{ $moment->label }}
</span>
