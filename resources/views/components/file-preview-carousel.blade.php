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
        x-on:keydown.left.prevent="prev()"
        x-on:keydown.right.prevent="next()"
        tabindex="0"
        role="region"
        aria-label="Bestandencarrousel"
        class="relative bg-[var(--color-bg-cream)] rounded-2xl mb-5 aspect-[4/3] overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2"
        data-carousel
    >
        {{-- Preview badge (top-left) --}}
        @if($hasMoreSlides)
            <span class="absolute top-3 left-0 z-10 text-[11px] font-semibold uppercase tracking-wide bg-[var(--color-bg-cream)] text-[var(--color-primary)] pl-3 pr-2.5 py-1 rounded-r border border-l-0 border-[var(--color-border-light)]" data-preview-counter>Preview</span>
        @endif

        {{-- Slide viewport --}}
        <div class="overflow-hidden h-full">
            <div
                class="flex transition-transform duration-300 ease-out h-full motion-reduce:transition-none"
                :style="`transform: translateX(-${current * 100}%)`"
            >
                @foreach($slides as $index => $slide)
                    <div class="w-full shrink-0 h-full flex items-center justify-center p-6 sm:p-10 pb-10 sm:pb-14">
                        @if($slide['type'] === 'preview')
                            <div class="relative bg-white rounded-sm max-h-full" style="box-shadow: 11px 13px 19px -7px rgba(120, 90, 60, 0.18); border: 1px solid rgba(120, 90, 60, 0.1);">
                                <img
                                    src="{{ $slide['url'] }}"
                                    alt="Preview van {{ $slide['filename'] }}"
                                    @if($index > 0) loading="lazy" @endif
                                    class="max-h-full w-auto rounded-sm"
                                    data-preview-image
                                >
                                @if($hasMoreSlides && $loop->last)
                                    <div class="absolute inset-0 rounded-sm bg-white flex flex-col items-center justify-center" style="border: 1px solid rgba(120, 90, 60, 0.1);" data-preview-overlay>
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
                            <div class="bg-white rounded-sm w-full max-w-[280px] aspect-[3/4] p-6 sm:p-8 flex flex-col" style="box-shadow: 11px 13px 19px -7px rgba(120, 90, 60, 0.18); border: 1px solid rgba(120, 90, 60, 0.1);" data-skeleton-card>
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
            class="absolute left-1 sm:left-2 top-1/2 -translate-y-[calc(50%+12px)] w-11 h-11 rounded-full bg-white/90 backdrop-blur-sm shadow-md flex items-center justify-center hover:bg-white active:scale-95 transition-all cursor-pointer"
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
            class="absolute right-1 sm:right-2 top-1/2 -translate-y-[calc(50%+12px)] w-11 h-11 rounded-full bg-white/90 backdrop-blur-sm shadow-md flex items-center justify-center hover:bg-white active:scale-95 transition-all cursor-pointer"
            aria-label="Volgende"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-text-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        {{-- Screen reader announcement --}}
        <div class="sr-only" aria-live="polite" x-text="`Slide ${current + 1} van ${total}`"></div>

        {{-- Dot indicators (inside fixed container) --}}
        @if($total > 1)
            <div class="absolute bottom-0 left-0 right-0 flex justify-center gap-0 pb-1">
                <template x-for="i in total" :key="i">
                    <button
                        x-on:click="goTo(i - 1)"
                        class="p-2 cursor-pointer group"
                        :aria-label="`Ga naar slide ${i}`"
                        :aria-current="current === i - 1 ? 'step' : undefined"
                    >
                        <span
                            class="block w-2 h-2 rounded-full transition-colors"
                            :class="current === i - 1 ? 'bg-[var(--color-primary)]' : 'bg-zinc-300 group-hover:bg-zinc-400'"
                        ></span>
                    </button>
                </template>
            </div>
        @endif
    </div>
@endif
