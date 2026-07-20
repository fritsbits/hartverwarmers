@php
    $publishedAt = \Illuminate\Support\Carbon::parse($update['published_at']);
    $isFresh = $publishedAt->gte(now()->subDays(\App\Services\ProductUpdates::FRESH_DAYS));
@endphp

<x-layout
    :title="$update['title']"
    :description="$update['body']"
    :og-image="isset($update['image']) ? asset(ltrim($update['image']['src'], '/')) : null"
    :full-width="true">

    {{-- Hero: date stamp, title, teaser as lead --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            <flux:breadcrumbs class="mb-10">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('whats-new') }}">Wat is er nieuw</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $update['title'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="max-w-4xl md:grid md:grid-cols-[5.5rem_1fr] md:gap-x-10 lg:gap-x-14">
                <div class="mb-6 md:mb-0">
                    <x-date-stamp
                        :date="$publishedAt"
                        :badge="$isFresh ? ['label' => 'Nieuw', 'emphatic' => true] : null" />
                </div>

                <div>
                    <h1 class="text-balance">{{ $update['title'] }}</h1>
                    <p class="text-[var(--color-text-secondary)] mt-6 max-w-[60ch] text-xl leading-relaxed text-pretty" style="font-weight: var(--font-weight-light);">
                        {{ $update['body'] }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Body: screenshot, long-form story, action --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="max-w-4xl md:grid md:grid-cols-[5.5rem_1fr] md:gap-x-10 lg:gap-x-14">
                <div class="hidden md:block" aria-hidden="true"></div>

                <div class="min-w-0">
                    @isset ($update['image'])
                        <figure class="mb-10">
                            <img src="{{ asset(ltrim($update['image']['src'], '/')) }}"
                                 alt="{{ $update['image']['alt'] }}"
                                 class="w-full rounded-[var(--radius-md)] border border-[var(--color-border-light)] shadow-card">
                        </figure>
                    @endisset

                    @if ($content)
                        <div class="update-content max-w-[68ch]">
                            {!! $content !!}
                        </div>
                    @endif

                    @isset ($update['link'])
                        <a href="{{ url($update['link']['url']) }}" class="btn-pill mt-10">{{ $update['link']['label'] }}</a>
                    @endisset
                </div>
            </div>
        </div>
    </section>

    {{-- Between updates --}}
    <section class="border-t border-[var(--color-border-light)] bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-10">
            <div class="max-w-4xl grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if ($older)
                    <a href="{{ route('whats-new.show', $older['uid']) }}"
                       class="group rounded-[var(--radius-md)] border border-[var(--color-border-light)] bg-[var(--color-bg-white)] px-5 py-4 transition-colors hover:border-[var(--color-border-hover)]">
                        <span class="text-sm text-[var(--color-text-secondary)]">Vorige update</span>
                        <span class="block font-heading font-bold mt-1 text-pretty transition-colors group-hover:text-[var(--color-primary)]">{{ $older['title'] }}</span>
                    </a>
                @else
                    <div class="hidden sm:block" aria-hidden="true"></div>
                @endif

                @if ($newer)
                    <a href="{{ route('whats-new.show', $newer['uid']) }}"
                       class="group rounded-[var(--radius-md)] border border-[var(--color-border-light)] bg-[var(--color-bg-white)] px-5 py-4 transition-colors hover:border-[var(--color-border-hover)] sm:text-right">
                        <span class="text-sm text-[var(--color-text-secondary)]">Volgende update</span>
                        <span class="block font-heading font-bold mt-1 text-pretty transition-colors group-hover:text-[var(--color-primary)]">{{ $newer['title'] }}</span>
                    </a>
                @endif
            </div>

            <div class="max-w-4xl mt-8">
                <a href="{{ route('whats-new') }}" class="cta-link">Bekijk alle updates</a>
            </div>
        </div>
    </section>

</x-layout>
