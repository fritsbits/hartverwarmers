@props(['objective', 'range'])

@php
    $startedAt = $objective->startedAt();
@endphp

<header class="mb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-baseline gap-3 flex-wrap">
                <flux:heading size="xl" class="font-heading font-bold">{{ $objective->title }}</flux:heading>
                <x-okr-status-badge :status="$objective->status" size="sm" />
                @if($objective->isArchived())
                    <span class="inline-flex items-center rounded-full font-medium bg-gray-100 text-gray-700 text-xs px-2 py-0.5">Gearchiveerd</span>
                @endif
            </div>

            @if($startedAt)
                <p data-testid="okr-since" class="text-xs text-[var(--color-text-secondary)] mt-1 tabular-nums">
                    @if($objective->isArchived())
                        Liep van {{ $startedAt->isoFormat('D MMMM YYYY') }} tot {{ $objective->archived_at->isoFormat('D MMMM YYYY') }}
                    @else
                        Loopt sinds {{ $startedAt->isoFormat('D MMMM YYYY') }}
                    @endif
                </p>
            @endif
        </div>

        <flux:dropdown position="bottom" align="end">
            <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" aria-label="OKR-acties" />

            <flux:menu>
                @if($objective->isArchived())
                    <form method="POST" action="{{ route('admin.okrs.unarchive', $objective) }}">
                        @csrf
                        <flux:menu.item type="submit" icon="arrow-uturn-left">Heractiveren</flux:menu.item>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.okrs.archive', $objective) }}">
                        @csrf
                        <flux:menu.item type="submit" icon="archive-box">Archiveren</flux:menu.item>
                    </form>
                @endif
            </flux:menu>
        </flux:dropdown>
    </div>

    @if($objective->description)
        <p class="text-sm text-[var(--color-text-secondary)] mt-2">{{ $objective->description }}</p>
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
