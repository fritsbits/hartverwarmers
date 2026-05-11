@props([])

<div x-data="{
    tip: null,
    show(el) {
        const rect = el.getBoundingClientRect();
        const wrapperRect = this.$el.getBoundingClientRect();
        this.tip = {
            label: el.dataset.tipLabel,
            value: el.dataset.tipValue,
            x: rect.left + rect.width / 2 - wrapperRect.left,
            y: rect.top - wrapperRect.top,
        };
    },
    hide() { this.tip = null; },
}"
    x-on:mouseover="if ($event.target.dataset.tipLabel) show($event.target)"
    x-on:mouseout="if ($event.target.dataset.tipLabel) hide()"
    class="relative"
>
    {{ $slot }}

    <div x-show="tip" x-cloak
         :style="`left: ${tip?.x}px; top: ${tip?.y}px;`"
         class="absolute z-20 -translate-x-1/2 -translate-y-full pointer-events-none px-2 py-1 rounded-md bg-[var(--color-text-primary)] text-[var(--color-bg-white)] text-xs whitespace-nowrap shadow-lg mb-1">
        <div class="font-semibold" x-text="tip?.label"></div>
        <div class="opacity-80 tabular-nums" x-text="tip?.value"></div>
    </div>
</div>
