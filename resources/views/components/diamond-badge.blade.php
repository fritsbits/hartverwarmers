@props(['card' => false])

<flux:dropdown>
    <button
        type="button"
        class="diamond-indicator cursor-pointer"
        @if($card) onclick="event.preventDefault(); event.stopPropagation();" @endif
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
        </svg>
        Diamantje
    </button>

    <flux:popover class="max-w-[20rem] space-y-3">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)]" fill="currentColor" viewBox="0 0 24 24">
                <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
            </svg>
            <flux:heading size="sm">Diamantje</flux:heading>
        </div>
        <flux:text class="text-sm">
            Deze fiche is door het team van Hartverwarmers uitgekozen als voorbeeldfiche.
            Ze illustreert op een heldere manier belangrijke aspecten van de DIAMANT-filosofie.
        </flux:text>
        <a
            href="{{ route('goals.index') }}"
            class="cta-link text-sm"
            @if($card) onclick="event.stopPropagation();" @endif
        >
            Meer over het DIAMANT-model
        </a>
    </flux:popover>
</flux:dropdown>
