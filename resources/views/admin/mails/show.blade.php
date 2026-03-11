<x-sidebar-layout title="{{ $email['label'] }}" section-label="E-mails">

    <section>
        {{-- Email header — mimics email client envelope --}}
        <div class="bg-white border border-[var(--color-border-light)] rounded-xl p-5 mb-4">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="min-w-0">
                    <h2 class="font-heading font-bold text-lg leading-tight">{{ $email['subject'] }}</h2>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $email['description'] }}</p>
                </div>
                <a href="{{ route('admin.mails.preview', $key) }}" target="_blank" class="shrink-0 inline-flex items-center gap-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors mt-0.5" title="Open in nieuw tabblad">
                    <flux:icon name="arrow-top-right-on-square" variant="mini" class="size-3.5" />
                    Fullscreen
                </a>
            </div>
            <div class="flex items-center gap-3 text-sm text-[var(--color-text-secondary)]">
                <div class="flex items-center gap-1.5">
                    <flux:icon name="envelope" variant="mini" class="size-3.5 text-[var(--color-text-secondary)] opacity-60" />
                    <span>{{ $email['from'] }}</span>
                </div>
            </div>
        </div>

        {{-- Email preview iframe --}}
        <div class="border border-[var(--color-border-light)] rounded-xl overflow-hidden">
            <iframe
                src="{{ route('admin.mails.preview', $key) }}"
                class="w-full border-0"
                style="min-height: 500px;"
                loading="lazy"
                onload="this.style.height = this.contentDocument.body.scrollHeight + 'px'"
            ></iframe>
        </div>
    </section>

    <p class="text-xs text-[var(--color-text-muted)] mt-10">
        Templates: <span class="font-mono">resources/views/vendor/mail/</span> en <span class="font-mono">resources/views/vendor/notifications/</span>
    </p>

</x-sidebar-layout>
