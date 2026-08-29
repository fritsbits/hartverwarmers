@props([
    'principes' => [],
    'reflectionQuestions' => [],
])

@php
    /**
     * Het papier draagt de vier principenamen zodra een doel ze heeft, in
     * dezelfde letter en volgorde als de chips op de klassiekerrijen eronder.
     * Doelen zonder principes houden hun oude checklist.
     */
    $isLegende = ! empty($principes);
@endphp

@if($isLegende || ! empty($reflectionQuestions))
    <div class="px-8 lg:-translate-y-12">
        <div class="quote-paper quote-paper-lg">
            <span class="checklist-label">Checklist</span>

            @if($isLegende)
                @foreach($principes as $principe)
                    <div class="principe-regel">
                        <span class="principe-naam">
                            <x-principe-gem :size="16" />
                            {{ $principe['naam'] }}
                        </span>
                        <p class="principe-toelichting">{{ $principe['toelichting'] }}</p>
                    </div>
                @endforeach
            @else
                @foreach($reflectionQuestions as $question)
                    <div class="checklist-item">
                        <span class="question-badge">
                            <x-principe-gem :size="14" />
                        </span>
                        <p class="font-body font-light">{{ $question }}</p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endif
