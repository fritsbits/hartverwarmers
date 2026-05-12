@props(['month', 'themesByDate' => []])

@php
    $first = $month->copy()->startOfMonth();
    $daysInMonth = $first->copy()->endOfMonth()->day;
    $leadingEmpty = $first->isoWeekday() - 1;
    $totalCells = $leadingEmpty + $daysInMonth;
    $rows = (int) ceil($totalCells / 7);
    $today = today();
    $monthLabel = $month->locale('nl_BE')->translatedFormat('F Y');
@endphp

<div class="select-none w-60 rotate-[1deg] bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-md shadow-[0_1px_2px_rgba(35,30,26,0.04),0_8px_16px_-4px_rgba(35,30,26,0.08),0_24px_48px_-12px_rgba(35,30,26,0.14)] overflow-hidden">
    {{-- Orange month band (acts as the real 'header' of the calendar page) --}}
    <div class="bg-[var(--color-primary)] text-center px-4 py-2.5 relative">
        <div class="font-heading font-bold text-base text-white lowercase tabular-nums tracking-wide">
            {{ $monthLabel }}
        </div>
        {{-- Subtle inner shadow under the band (paper meets the colored top) --}}
        <div class="absolute inset-x-0 bottom-0 h-1.5 translate-y-full pointer-events-none"
             style="background: linear-gradient(to bottom, rgba(35,30,26,0.10), transparent);"></div>
    </div>

    {{-- Paper body with subtle grain --}}
    <div class="relative"
         style="background-image: repeating-linear-gradient(0deg, rgba(35,30,26,0.014) 0px, rgba(35,30,26,0.014) 1px, transparent 1px, transparent 3px);">

        {{-- Weekday header row --}}
        <div class="px-3 pt-3 pb-1">
            <div class="grid grid-cols-7 text-center">
                @foreach(['ma','di','wo','do','vr','za','zo'] as $wd)
                    <div class="text-[10px] uppercase tracking-widest text-[var(--color-text-tertiary)] font-semibold">{{ $wd }}</div>
                @endforeach
            </div>
        </div>

        {{-- Day rows with zebra striping --}}
        <div class="px-3 pb-3">
            @for($row = 0; $row < $rows; $row++)
                <div class="grid grid-cols-7 text-center {{ $row % 2 === 1 ? 'bg-[var(--color-bg-cream)]' : '' }}">
                    @for($col = 0; $col < 7; $col++)
                        @php
                            $cellIndex = $row * 7 + $col;
                            $day = $cellIndex - $leadingEmpty + 1;
                        @endphp
                        @if($day < 1 || $day > $daysInMonth)
                            <div class="h-8"></div>
                        @else
                            @php
                                $thisDate = $first->copy()->setDay($day);
                                $key = $thisDate->format('Y-m-d');
                                $themesHere = $themesByDate[$key] ?? null;
                                $isToday = $thisDate->isSameDay($today);

                                if ($themesHere) {
                                    $first_slug = $themesHere[0]['slug'];
                                    $tooltipParts = array_map(function ($t) {
                                        $count = $t['fiche_count'];
                                        $countText = $count > 0
                                            ? ' · '.$count.' '.($count === 1 ? 'fiche' : 'fiches')
                                            : '';

                                        return $t['title'].$countText;
                                    }, $themesHere);
                                    $tooltipText = implode(' • ', $tooltipParts);
                                    $href = route('themes.index', ['maand' => $month->format('Y-m')]).'#thema-'.$first_slug;
                                }

                                $cellClass = 'inline-flex items-center justify-center w-7 h-7 text-xs tabular-nums leading-none rounded-full transition-[background-color,color,transform] duration-150';
                                if ($isToday) {
                                    $cellClass .= ' bg-[var(--color-primary)] text-white font-semibold';
                                } elseif ($themesHere) {
                                    $cellClass .= ' text-[var(--color-primary)] font-semibold hover:bg-[var(--color-bg-accent-light)] active:scale-[0.96]';
                                } else {
                                    $cellClass .= ' text-[var(--color-text-secondary)]';
                                }
                            @endphp
                            <div class="flex items-center justify-center h-8">
                                @if($themesHere)
                                    <flux:tooltip :content="$tooltipText">
                                        <a href="{{ $href }}" class="{{ $cellClass }}">{{ $day }}</a>
                                    </flux:tooltip>
                                @else
                                    <span class="{{ $cellClass }}">{{ $day }}</span>
                                @endif
                            </div>
                        @endif
                    @endfor
                </div>
            @endfor
        </div>
    </div>
</div>
