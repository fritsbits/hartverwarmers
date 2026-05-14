@if($currentObjective)
    <x-okr-tab :objective="$currentObjective" :range="$range">
        <x-slot:key-results>
            @foreach($currentObjective->keyResults as $kr)
                <x-okr-kr :kr="$kr" :range="$range" />
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
            @include('admin.context.nieuwsbrief')
        </x-slot:context>
    </x-okr-tab>
@endif
