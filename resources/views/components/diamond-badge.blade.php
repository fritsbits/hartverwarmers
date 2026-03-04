<flux:dropdown>
    <button type="button" class="diamond-indicator cursor-pointer">
        <x-diamant-gem letter="" size="xxs" :pronounced="true" />
        Diamantje
    </button>

    <flux:popover class="max-w-[20rem] space-y-3">
        <div class="flex items-center gap-2">
            <x-diamant-gem letter="" size="xs" :pronounced="true" />
            <flux:heading size="sm">Diamantje</flux:heading>
        </div>
        <flux:text class="text-sm">
            Deze fiche is door het team van Hartverwarmers uitgekozen als voorbeeldfiche.
            Ze illustreert op een heldere manier belangrijke aspecten van de DIAMANT-filosofie.
        </flux:text>
        <a href="{{ route('goals.index') }}" class="cta-link text-sm">
            Meer over het DIAMANT-model
        </a>
    </flux:popover>
</flux:dropdown>
