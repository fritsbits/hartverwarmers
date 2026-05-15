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
