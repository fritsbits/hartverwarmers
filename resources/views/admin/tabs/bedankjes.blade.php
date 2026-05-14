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
            <div class="grid gap-4">
                <x-okr-review-card
                    title="Recente bedank-reacties"
                    subtitle="Wat schrijven downloaders terug?"
                    :items="$recentThankComments"
                    empty="Nog geen bedank-reacties in deze periode."
                />

                <x-okr-review-card
                    title="Vaakst bedankt · {{ $rangeLabel }}"
                    subtitle="Welke fiches resoneren?"
                    :items="$topThankedFiches"
                    empty="Nog geen bedankjes in deze periode."
                />

                @include('admin.context.bedankjes')
            </div>
        </x-slot:context>
    </x-okr-tab>
@endif
