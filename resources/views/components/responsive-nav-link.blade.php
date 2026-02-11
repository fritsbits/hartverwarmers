@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block px-3 py-2 rounded-md text-base font-medium bg-[var(--color-primary)] text-white'
            : 'block px-3 py-2 rounded-md text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
