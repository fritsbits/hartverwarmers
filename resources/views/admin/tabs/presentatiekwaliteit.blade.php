@if($currentObjective)
    <x-okr-tab :objective="$currentObjective" :range="$range">
        <x-slot:key-results>
            @foreach($currentObjective->keyResults as $kr)
                <x-okr-kr :kr="$kr" :range="$range">
                    @if($kr->metric_key === 'presentation_score_avg')
                        @include('admin.partials.fragments.presentation-score-trend')
                    @endif
                </x-okr-kr>
            @endforeach
        </x-slot:key-results>

        <x-slot:initiatives>
            @foreach($currentObjective->initiatives as $initiative)
                <x-okr-initiative :initiative="$initiative">
                    @include('admin.initiatives.' . $initiative->slug)
                </x-okr-initiative>
            @endforeach
        </x-slot:initiatives>

        <x-slot:context>
            <div class="grid gap-4">
                @include('admin.context.presentatiekwaliteit')

                <x-okr-review-card
                    title="Laagste 5 scores"
                    subtitle="Fiches met het meeste verbeterpotentieel"
                    :items="$lowestScoredFiches"
                    empty="Nog geen beoordeelde fiches."
                />

                <x-okr-review-card
                    title="Recente AI-acceptances"
                    subtitle="Welke suggesties namen bijdragers over?"
                    :items="$recentAiSuggestionAcceptances"
                    empty="Nog geen overgenomen suggesties."
                />
            </div>
        </x-slot:context>
    </x-okr-tab>
@endif
