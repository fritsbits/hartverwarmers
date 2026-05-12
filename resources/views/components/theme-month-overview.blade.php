@props(['month', 'datesWithThemes' => []])

@php
    $first = $month->copy()->startOfMonth();
    $daysInMonth = $first->copy()->endOfMonth()->day;
    $leadingEmpty = $first->isoWeekday() - 1; // ISO Mon=1..Sun=7 → empty cells before day 1
    $today = today();

    $datesSet = collect($datesWithThemes)
        ->map(fn ($d) => $d instanceof \Carbon\CarbonInterface ? $d->format('Y-m-d') : $d)
        ->flip()
        ->all();
@endphp

<div aria-hidden="true" class="select-none w-64 bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-md shadow-[0_2px_4px_rgba(35,30,26,0.06)] p-5">
    <div class="grid grid-cols-7 gap-y-2 text-center">
        @foreach(['ma','di','wo','do','vr','za','zo'] as $wd)
            <div class="text-[10px] uppercase tracking-widest text-[var(--color-text-tertiary)] font-semibold pb-2">{{ $wd }}</div>
        @endforeach
        @for($i = 0; $i < $leadingEmpty; $i++)
            <div></div>
        @endfor
        @for($day = 1; $day <= $daysInMonth; $day++)
            @php
                $thisDate = $first->copy()->setDay($day);
                $hasTheme = isset($datesSet[$thisDate->format('Y-m-d')]);
                $isToday = $thisDate->isSameDay($today);
            @endphp
            <div class="flex flex-col items-center gap-1">
                <span class="text-xs tabular-nums leading-none w-6 h-6 flex items-center justify-center rounded-full
                    {{ $isToday ? 'bg-[var(--color-primary)] text-white font-semibold' : ($hasTheme ? 'text-[var(--color-primary)] font-semibold' : 'text-[var(--color-text-secondary)]') }}">
                    {{ $day }}
                </span>
                <span class="w-1.5 h-1.5 rounded-full {{ $hasTheme && ! $isToday ? 'bg-[var(--color-primary)]' : 'bg-transparent' }}"></span>
            </div>
        @endfor
    </div>
</div>
