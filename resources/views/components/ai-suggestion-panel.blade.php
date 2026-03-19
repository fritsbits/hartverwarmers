@props(['suggestion', 'rawSuggestion' => null, 'field', 'isApplied' => false])

@php
    $raw = $rawSuggestion ?? $suggestion;
@endphp

<div
    x-data="{ applied: false, rawContent: @js($raw), field: @js($field) }"
    class="flex gap-2.5 py-4 pl-2 pr-4 text-sm text-[var(--color-text-primary)]/70"
>
    <flux:icon.sparkles class="w-5 h-5 shrink-0 text-[var(--color-primary)] mt-0.5" />
    <div class="min-w-0">
        <div class="text-xs font-semibold text-[var(--color-text-secondary)] mb-3 uppercase tracking-wider">Suggestie</div>
        <div class="text-sm max-w-none [&_strong]:text-[var(--color-text-primary)]/80 [&_ul]:list-disc [&_ul]:pl-4 [&_ul]:space-y-1.5 [&_ol]:list-decimal [&_ol]:pl-4 [&_ol]:space-y-1.5 [&_p+p]:mt-2">{!! $suggestion !!}</div>
        <div class="mt-3">
            @if($isApplied)
                <span class="inline-flex items-center gap-1 h-7 px-2 text-xs font-medium text-[var(--color-text-secondary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Toegevoegd
                </span>
            @else
                <span
                    x-show="applied"
                    x-cloak
                    class="inline-flex items-center gap-1 h-7 px-2 text-xs font-medium text-[var(--color-text-secondary)]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Toegevoegd
                </span>
                <flux:button
                    x-show="!applied"
                    x-cloak
                    size="xs"
                    variant="filled"
                    x-on:click="
                        if (field === 'title') {
                            const el = document.querySelector('input[name=title]');
                            if (el) {
                                const current = el.value.trim();
                                el.value = current ? current + ' ' + rawContent : rawContent;
                                el.dispatchEvent(new Event('input', { bubbles: true }));
                                el.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        } else {
                            const editorEl = Array.from(document.querySelectorAll('ui-editor')).find(el => el.getAttribute('wire:model') === field);
                            if (editorEl && editorEl.editor) {
                                if (editorEl.editor.isEmpty) {
                                    editorEl.editor.commands.setContent(rawContent);
                                } else {
                                    const { doc } = editorEl.editor.state;
                                    editorEl.editor.commands.insertContentAt(doc.content.size, rawContent);
                                }
                            }
                        }
                        $wire.trackApplied(field);
                        applied = true;
                    "
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Toevoegen
                </flux:button>
            @endif
        </div>
    </div>
</div>
