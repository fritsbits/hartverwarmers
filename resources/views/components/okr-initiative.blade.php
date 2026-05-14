@props(['initiative'])

<flux:card>
    <div class="flex items-start justify-between mb-2">
        <flux:heading size="lg" class="font-heading font-bold">{{ $initiative->label }}</flux:heading>
        <x-okr-status-badge :status="$initiative->status" size="sm" />
    </div>
    @if($initiative->description)
        <p class="text-sm text-[var(--color-text-secondary)] mb-4">{{ $initiative->description }}</p>
    @endif
    {{ $slot }}
</flux:card>
