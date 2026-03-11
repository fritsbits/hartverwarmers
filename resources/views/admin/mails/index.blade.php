<x-sidebar-layout title="E-mails" section-label="Beheer">

    <div class="space-y-10">
        @foreach($emails as $key => $email)
            <section>
                <div class="flex items-baseline gap-3 mb-2">
                    <h2 class="font-heading font-bold text-lg">{{ $email['label'] }}</h2>
                    <span class="text-sm text-[var(--color-text-muted)]">{{ $email['description'] }}</span>
                    <a href="{{ route('admin.mails.preview', $key) }}" target="_blank" class="ml-auto text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors" title="Open in nieuw tabblad">
                        <flux:icon name="arrow-top-right-on-square" variant="mini" class="size-4" />
                    </a>
                </div>

                <dl class="flex flex-wrap gap-x-6 gap-y-1 text-sm mb-3">
                    <div class="flex gap-1.5">
                        <dt class="text-[var(--color-text-muted)]">Onderwerp:</dt>
                        <dd class="font-medium">{{ $email['subject'] }}</dd>
                    </div>
                    <div class="flex gap-1.5">
                        <dt class="text-[var(--color-text-muted)]">Afzender:</dt>
                        <dd class="font-medium">{{ $email['from'] }}</dd>
                    </div>
                </dl>

                <div class="border border-[var(--color-border-light)] rounded-lg overflow-hidden">
                    <iframe
                        src="{{ route('admin.mails.preview', $key) }}"
                        class="w-full border-0"
                        style="min-height: 500px;"
                        loading="lazy"
                        onload="this.style.height = this.contentDocument.body.scrollHeight + 'px'"
                    ></iframe>
                </div>
            </section>
        @endforeach
    </div>

    <p class="text-xs text-[var(--color-text-muted)] mt-10">
        Templates: <span class="font-mono">resources/views/vendor/mail/</span> en <span class="font-mono">resources/views/vendor/notifications/</span>
    </p>

</x-sidebar-layout>
