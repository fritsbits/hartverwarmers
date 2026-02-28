@props(['users', 'count' => null, 'max' => 5])

@php
    $total = $count ?? $users->count();
    $displayed = $users->take($max);
    $remaining = $total - $displayed->count();
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <div class="flex -space-x-2">
        @foreach($displayed as $user)
            <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-xs font-semibold ring-2 ring-white" title="{{ $user->full_name }}">
                {{ substr($user->first_name, 0, 1) }}
            </div>
        @endforeach
        @if($remaining > 0)
            <div class="w-8 h-8 rounded-full bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)] flex items-center justify-center text-xs font-semibold ring-2 ring-white">
                +{{ $remaining }}
            </div>
        @endif
    </div>
</div>
