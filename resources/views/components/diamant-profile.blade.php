@props(['goalTags' => collect()])

@php
    $facets = config('diamant.facets');
    $activeGoalSlugs = $goalTags->pluck('slug')->map(fn ($s) => str_replace('doel-', '', $s))->toArray();
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @foreach($facets as $slug => $facet)
        <x-diamant-gem :letter="$facet['letter']" size="sm"
            :active="in_array($slug, $activeGoalSlugs)" :title="$facet['keyword']" />
    @endforeach
</div>
