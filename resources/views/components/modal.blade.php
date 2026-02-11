@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$size = match ($maxWidth) {
    'sm' => 'sm',
    'md' => 'md',
    'lg' => 'lg',
    'xl' => 'xl',
    '2xl' => '2xl',
    default => 'md',
};
@endphp

<flux:modal
    name="{{ $name }}"
    :variant="$show ? 'bare' : 'default'"
    class="max-w-{{ $size }}"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? $flux.modal('{{ $name }}').show() : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? $flux.modal('{{ $name }}').close() : null"
>
    <div class="p-6">
        {{ $slot }}
    </div>
</flux:modal>
