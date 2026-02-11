@props(['status'])

@if ($status)
    <flux:callout variant="success" {{ $attributes }}>
        {{ $status }}
    </flux:callout>
@endif
