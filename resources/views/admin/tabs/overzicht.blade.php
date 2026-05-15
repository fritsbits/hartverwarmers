@if(($objectiveStats ?? collect())->isNotEmpty())
    <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Objectieven</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        @foreach($objectiveStats as $stat)
            @include('admin.partials.objective-stat-card', ['stat' => $stat, 'range' => $range])
        @endforeach
    </div>
@endif

<p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Initiatieven</p>
@if($initiativeSummaries->isEmpty() && $plannedInitiatives->isEmpty())
    <flux:card>
        <p class="text-sm text-[var(--color-text-secondary)]">
            Nog geen initiatieven geregistreerd. Voeg er een toe en stel een startdatum in om de impact op de OKR's te volgen.
        </p>
    </flux:card>
@else
    <div class="bg-white rounded-xl border border-[var(--color-border-light)] divide-y divide-[var(--color-border-light)]">
        @foreach($initiativeSummaries as $entry)
            @include('admin.partials.initiative-row', ['initiative' => $entry['initiative'], 'headline' => $entry['headline']])
        @endforeach
    </div>

    @if($plannedInitiatives->isNotEmpty())
        <section class="mt-10">
            <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Gepland</p>
            <div class="grid gap-3">
                @foreach($plannedInitiatives as $planned)
                    <a href="?tab={{ $planned->objective->slug }}&init={{ $planned->slug }}" class="block">
                        <flux:card class="hover:shadow-sm transition-shadow opacity-75">
                            <div class="flex items-center justify-between gap-4">
                                <flux:heading size="md" class="font-heading font-bold">{{ $planned->label }}</flux:heading>
                                <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] whitespace-nowrap">{{ $planned->objective->title }} &rarr;</span>
                            </div>
                        </flux:card>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endif
