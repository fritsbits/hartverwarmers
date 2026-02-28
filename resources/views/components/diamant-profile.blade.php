@props(['goalTags' => collect()])

@php
    $facets = config('diamant.facets');
    $activeGoalSlugs = $goalTags->pluck('slug')->map(fn ($s) => str_replace('doel-', '', $s))->toArray();
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @foreach($facets as $slug => $facet)
        @if(in_array($slug, $activeGoalSlugs))
            <span class="diamant-badge-sm" title="{{ $facet['keyword'] }}">{{ $facet['letter'] }}</span>
        @else
            <span class="diamant-badge-sm-inactive" title="{{ $facet['keyword'] }}">{{ $facet['letter'] }}</span>
        @endif
    @endforeach
</div>
