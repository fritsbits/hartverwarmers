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
            <div class="grid gap-4">
                @if($lastNewsletterDigestMeta)
                    <flux:card>
                        <flux:heading size="lg" class="font-heading font-bold mb-1">Laatste digest</flux:heading>
                        <p class="text-sm text-[var(--color-text-secondary)] mb-4">Wat ging er meest recent uit?</p>
                        <div class="flex flex-wrap gap-6 tabular-nums">
                            <div>
                                <div class="text-2xl font-bold text-[var(--color-primary)]">Cyclus {{ $lastNewsletterDigestMeta['cycle'] }}</div>
                                <div class="text-xs text-[var(--color-text-secondary)]">{{ $lastNewsletterDigestMeta['sent_at']->isoFormat('D MMM YYYY') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">{{ $lastNewsletterDigestMeta['recipients'] }}</div>
                                <div class="text-xs text-[var(--color-text-secondary)]">ontvangers</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">{{ $lastNewsletterDigestMeta['sent_at']->diffForHumans() }}</div>
                                <div class="text-xs text-[var(--color-text-secondary)]">verstuurd</div>
                            </div>
                        </div>
                    </flux:card>
                @endif

                <x-okr-review-card
                    title="Recente uitschrijvingen"
                    subtitle="Wie schreef zich uit en na welke cyclus?"
                    :items="$recentUnsubscribes"
                    empty="Geen uitschrijvingen om te tonen."
                />

                @include('admin.context.nieuwsbrief')
            </div>
        </x-slot:context>
    </x-okr-tab>
@endif
