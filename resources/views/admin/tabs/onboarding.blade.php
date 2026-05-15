@if($currentObjective)
    <x-okr-tab :objective="$currentObjective" :range="$range">
        <x-slot:key-results>
            @foreach($currentObjective->keyResults as $kr)
                <x-okr-kr :kr="$kr" :range="$range" :step="$loop->iteration">
                    @if($kr->metric_key === 'onboarding_signup_count')
                        @include('admin.partials.fragments.signup-trend')
                    @endif
                </x-okr-kr>
            @endforeach
        </x-slot:key-results>

        <x-slot:initiatives>
            @forelse($initiativeSummaries as $entry)
                @include('admin.partials.initiative-section', [
                    'initiative' => $entry['initiative'],
                    'summary' => $entry['summary'],
                    'contextView' => $entry['initiative']->slug === 'onboarding-emails'
                        ? 'admin.context.onboarding-initiative'
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
