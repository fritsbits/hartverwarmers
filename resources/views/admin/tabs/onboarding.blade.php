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
            @foreach($currentObjective->initiatives as $initiative)
                <x-okr-initiative :initiative="$initiative">
                    @include('admin.initiatives.' . $initiative->slug)
                </x-okr-initiative>
            @endforeach
        </x-slot:initiatives>
    </x-okr-tab>
@endif
