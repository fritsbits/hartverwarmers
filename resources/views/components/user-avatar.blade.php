@props(['user', 'size' => 'md'])

@php
    // Deterministic color palette — derived from user ID so each person always gets the same color
    $avatarColors = [
        ['bg' => 'bg-[#FDF3EE]', 'text' => 'text-[var(--color-primary)]'],      // orange
        ['bg' => 'bg-[#E8F6F8]', 'text' => 'text-[#3A9BA8]'],                   // teal
        ['bg' => 'bg-[#FEF6E0]', 'text' => 'text-[#B08A22]'],                   // yellow
        ['bg' => 'bg-[#F3E8F3]', 'text' => 'text-[#9A5E98]'],                   // purple
    ];
    $color = $avatarColors[($user->id ?? 0) % count($avatarColors)];
    $initials = mb_strtoupper(mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
    if ($initials === '') {
        $initials = mb_strtoupper(mb_substr($user->name ?? '?', 0, 1));
    }

    $sizeMap = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'base' => 'w-12 h-12 text-sm',
        'lg' => 'w-14 h-14 text-base',
        'xl' => 'w-16 h-16 text-lg',
        '2xl' => 'w-28 h-28 md:w-40 md:h-40 text-5xl md:text-6xl',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

@if($user->avatar_path)
    <img src="{{ $user->avatarUrl() }}"
         alt="{{ $user->full_name }}"
         {{ $attributes->merge(['class' => "$sizeClass rounded-full object-cover shrink-0"]) }}
         loading="lazy">
@else
    <div {{ $attributes->merge(['class' => "$sizeClass rounded-full $color[bg] $color[text] flex items-center justify-center font-bold shrink-0"]) }}
         title="{{ $user->full_name }}">
        {{ $initials }}
    </div>
@endif
