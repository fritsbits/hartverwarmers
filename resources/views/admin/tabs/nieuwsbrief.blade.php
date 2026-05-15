@if($currentObjective)
    <x-okr-tab :objective="$currentObjective" :range="$range">
        <x-slot:key-results>
            @foreach($currentObjective->keyResults as $kr)
                <x-okr-kr :kr="$kr" :range="$range" />
            @endforeach
        </x-slot:key-results>

        <x-slot:initiatives>
            @forelse($initiativeSummaries as $entry)
                @include('admin.partials.initiative-section', [
                    'initiative' => $entry['initiative'],
                    'summary' => $entry['summary'],
                    'contextView' => $entry['initiative']->slug === 'nieuwsbrief-systeem'
                        ? 'admin.context.nieuwsbrief-initiative'
                        : null,
                ])
            @empty
                <flux:card>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Nog geen initiatief gestart voor dit doel.
                    </p>
                </flux:card>
            @endforelse
        </x-slot:initiatives>
    </x-okr-tab>
@endif
