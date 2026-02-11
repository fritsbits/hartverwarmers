@props(['align' => 'right', 'width' => '48'])

@php
$position = match ($align) {
    'left' => 'start',
    default => 'end',
};
@endphp

<flux:dropdown position="bottom" align="{{ $position }}">
    <flux:button variant="ghost">
        {{ $trigger }}
    </flux:button>

    <flux:menu>
        {{ $content }}
    </flux:menu>
</flux:dropdown>
