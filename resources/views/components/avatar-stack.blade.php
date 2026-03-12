@props(['users', 'count' => null, 'max' => 5, 'size' => 'md'])

@php
    $total = $count ?? $users->count();
    $displayed = $users->take($max);
    $remaining = $total - $displayed->count();
    $sizeClasses = $size === 'sm' ? 'w-6 h-6 text-[10px]' : 'w-8 h-8 text-xs';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <div class="flex -space-x-2">
        @foreach($displayed as $user)
            <x-user-avatar :user="$user" :size="$size === 'sm' ? 'xs' : 'sm'" class="ring-2 ring-white" />
        @endforeach
        @if($remaining > 0)
            <div class="{{ $sizeClasses }} rounded-full bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)] flex items-center justify-center font-semibold ring-2 ring-white">
                +{{ $remaining }}
            </div>
        @endif
    </div>
</div>
