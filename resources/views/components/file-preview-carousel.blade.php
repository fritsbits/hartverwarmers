@props(['files' => collect(), 'downloadUrl' => null, 'downloadLabel' => null, 'downloadSize' => null])

@php
    $previews = [];
    $dummies = [];
    $totalSlides = null;
    $pageUnit = 'slides';
    foreach ($files as $file) {
        if ($file->hasPreviewImages()) {
            if ($totalSlides === null) {
                $totalSlides = $file->total_slides;
                $pageUnit = $file->pageUnitLabel();
            }
            foreach ($file->preview_images as $path) {
                $previews[] = ['type' => 'preview', 'url' => Storage::url($path), 'filename' => $file->original_filename];
            }
        } else {
            $dummies[] = ['type' => 'dummy', 'file' => $file];
        }
    }
    // Only show skeletons when there are no real previews at all
    $slides = count($previews) > 0 ? $previews : $dummies;
    $total = count($slides);
    $previewCount = count($previews);
    $hasMoreSlides = $totalSlides && $totalSlides > $previewCount;
    $remaining = $hasMoreSlides ? $totalSlides - $previewCount : 0;
@endphp

@if($total > 0)
    <div
        x-data="{
            current: 0,
            total: {{ $total }},
            touchStartX: 0,
            next() { if (this.current < this.total - 1) this.current++ },
            prev() { if (this.current > 0) this.current-- },
            goTo(i) { this.current = i },
        }"
        x-on:touchstart="touchStartX = $event.changedTouches[0].screenX"
        x-on:touchend="
            let diff = touchStartX - $event.changedTouches[0].screenX;
            if (diff > 50) next();
            if (diff < -50) prev();
        "
        class="relative bg-[var(--color-bg-subtle)] rounded-2xl mb-5"
        data-carousel
    >
        {{-- Preview badge (top-left) --}}
        @if($hasMoreSlides)
            <span class="absolute top-3 left-0 z-10 text-[11px] font-semibold uppercase tracking-wide bg-[var(--color-bg-cream)] text-[var(--color-primary)] pl-3 pr-2.5 py-1 rounded-r border border-l-0 border-[var(--color-border-light)]" data-preview-counter>Preview</span>
        @endif

        {{-- Slide viewport --}}
        <div class="overflow-hidden">
            <div
                class="flex transition-transform duration-300 ease-in-out"
                :style="`transform: translateX(-${current * 100}%)`"
            >
                @foreach($slides as $index => $slide)
                    <div class="w-full shrink-0 px-8 pt-8">
                        @if($slide['type'] === 'preview')
                            <div class="relative w-full">
                                <img
                                    src="{{ $slide['url'] }}"
                                    alt="Preview van {{ $slide['filename'] }}"
                                    loading="lazy"
                                    class="w-full rounded-lg shadow-md"
                                    data-preview-image
                                >
                                @if($hasMoreSlides && $loop->last)
                                    <div class="absolute inset-0 rounded-lg bg-white border border-[var(--color-border-light)] flex flex-col items-center justify-center" data-preview-overlay>
                                        <span class="text-3xl font-heading font-bold text-[var(--color-primary)]">+{{ $remaining }}</span>
                                        <span class="text-sm text-[var(--color-text-secondary)]">{{ $pageUnit }} in dit bestand</span>
                                        @if($downloadUrl && $downloadLabel)
                                            <div class="mt-3">
                                                <flux:button variant="primary" icon="arrow-down-tray"
                                                    href="{{ $downloadUrl }}">
                                                    {{ $downloadLabel }}
                                                    @if($downloadSize)
                                                        <span class="text-xs font-normal text-white/70">{{ $downloadSize }}</span>
                                                    @endif
                                                </flux:button>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- Dummy skeleton card --}}
                            @php $file = $slide['file']; @endphp
                            <div class="bg-white rounded-lg shadow-md w-full max-w-[280px] mx-auto aspect-[3/4] p-6 sm:p-8 flex flex-col" data-skeleton-card>
                                <div class="flex-1 flex flex-col gap-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        @php
                                            $ext = strtoupper(pathinfo($file->original_filename, PATHINFO_EXTENSION));
                                            $badgeColor = match(true) {
                                                str_contains($file->mime_type, 'pdf') => 'bg-red-100 text-red-700',
                                                str_contains($file->mime_type, 'image') => 'bg-blue-100 text-blue-700',
                                                str_contains($file->mime_type, 'word') || str_contains($file->mime_type, 'document') => 'bg-blue-100 text-blue-700',
                                                str_contains($file->mime_type, 'presentation') || str_contains($file->mime_type, 'powerpoint') => 'bg-orange-100 text-orange-700',
                                                str_contains($file->mime_type, 'spreadsheet') || str_contains($file->mime_type, 'excel') => 'bg-green-100 text-green-700',
                                                default => 'bg-zinc-100 text-zinc-600',
                                            };
                                        @endphp
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $badgeColor }}">{{ $ext }}</span>
                                    </div>
                                    <div class="h-3 rounded-full bg-zinc-200 w-4/5"></div>
                                    <div class="h-3 rounded-full bg-zinc-200 w-3/5"></div>
                                    <div class="h-2"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-full"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-11/12"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-full"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-4/5"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-full"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-3/4"></div>
                                    <div class="h-2"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-full"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-5/6"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-full"></div>
                                    <div class="h-2 rounded-full bg-zinc-100 w-2/3"></div>
                                </div>
                                <p class="text-xs text-[var(--color-text-secondary)] truncate mt-3 pt-3 border-t border-zinc-100">
                                    {{ $file->original_filename }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Previous button --}}
        <button
            x-show="current > 0"
            x-on:click="prev()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute left-2 sm:left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white shadow-md flex items-center justify-center hover:bg-zinc-50 transition-colors cursor-pointer"
            aria-label="Vorige"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-text-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>

        {{-- Next button --}}
        <button
            x-show="current < total - 1"
            x-on:click="next()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white shadow-md flex items-center justify-center hover:bg-zinc-50 transition-colors cursor-pointer"
            aria-label="Volgende"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-text-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        {{-- Dot indicators --}}
        @if($total > 1)
            <div class="flex justify-center gap-1.5 pt-4 pb-4">
                <template x-for="i in total" :key="i">
                    <button
                        x-on:click="goTo(i - 1)"
                        class="w-2 h-2 rounded-full transition-colors cursor-pointer"
                        :class="current === i - 1 ? 'bg-[var(--color-primary)]' : 'bg-zinc-300 hover:bg-zinc-400'"
                        :aria-label="`Ga naar slide ${i}`"
                    ></button>
                </template>
            </div>
        @endif
    </div>
@endif
