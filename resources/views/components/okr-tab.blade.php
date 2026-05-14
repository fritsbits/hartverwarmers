@props(['objective', 'range'])

<header class="mb-6">
    <div class="flex items-baseline gap-3">
        <flux:heading size="xl" class="font-heading font-bold">{{ $objective->title }}</flux:heading>
        <x-okr-status-badge :status="$objective->status" size="sm" />
    </div>
    @if($objective->description)
        <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $objective->description }}</p>
    @endif
</header>

@isset($keyResults)
    <section class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Key results</p>
        <div class="bg-white rounded-xl border border-[var(--color-border-light)] divide-y divide-[var(--color-border-light)]">
            {{ $keyResults }}
        </div>
    </section>
@endisset

@isset($initiatives)
    <section class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Initiatieven</p>
        <div class="grid gap-4">{{ $initiatives }}</div>
    </section>
@endisset

@isset($context)
    <section class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)] mb-3">Context</p>
        {{ $context }}
    </section>
@endisset
