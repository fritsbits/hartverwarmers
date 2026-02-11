@props(['active'])

@php
$classes = ($active ?? false)
            ? 'px-3 py-2 text-sm font-medium text-[var(--color-primary)] border-b-2 border-[var(--color-primary)]'
            : 'px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] border-b-2 border-transparent hover:border-[var(--color-border-light)] transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
