@if($currentObjective)
    <x-okr-tab :objective="$currentObjective" :range="$range">
        <x-slot:key-results>
            @foreach($currentObjective->keyResults as $kr)
                <x-okr-kr :kr="$kr" :range="$range">
                    @if($kr->metric_key === 'thank_rate')
                        @include('admin.partials.fragments.thank-rate-trend')
                    @endif
                </x-okr-kr>
            @endforeach
        </x-slot:key-results>

        <x-slot:context>
            @include('admin.context.bedankjes')
        </x-slot:context>
    </x-okr-tab>
@endif
