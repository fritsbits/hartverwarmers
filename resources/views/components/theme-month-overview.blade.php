@props(['month', 'themesByDate' => []])

@php
    $first = $month->copy()->startOfMonth();
    $daysInMonth = $first->copy()->endOfMonth()->day;
    $leadingEmpty = $first->isoWeekday() - 1;
    $today = today();
    $monthLabel = $month->locale('nl_BE')->translatedFormat('F Y');
@endphp

<div class="select-none w-64 bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-md shadow-[0_6px_16px_-4px_rgba(35,30,26,0.12),0_2px_4px_rgba(35,30,26,0.06)] overflow-hidden">
    {{-- Paper header --}}
    <div class="text-center px-4 pt-4 pb-3 border-b border-[var(--color-border-light)]">
        <div class="font-heading font-bold text-base text-[var(--color-primary)] lowercase tabular-nums">
            {{ $monthLabel }}
        </div>
    </div>

    {{-- Calendar grid --}}
    <div class="px-4 pt-3 pb-4">
        <div class="grid grid-cols-7 gap-y-1.5 text-center">
            @foreach(['ma','di','wo','do','vr','za','zo'] as $wd)
                <div class="text-[10px] uppercase tracking-widest text-[var(--color-text-tertiary)] font-semibold pb-2">{{ $wd }}</div>
            @endforeach
            @for($i = 0; $i < $leadingEmpty; $i++)
                <div></div>
            @endfor
            @for($day = 1; $day <= $daysInMonth; $day++)
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

                    $cellClass = 'inline-flex items-center justify-center w-8 h-8 text-xs tabular-nums leading-none rounded-full transition-[background-color,color,transform] duration-150';
                    if ($isToday) {
                        $cellClass .= ' bg-[var(--color-primary)] text-white font-semibold';
                    } elseif ($themesHere) {
                        $cellClass .= ' text-[var(--color-primary)] font-semibold hover:bg-[var(--color-bg-accent-light)] active:scale-[0.96]';
                    } else {
                        $cellClass .= ' text-[var(--color-text-secondary)]';
                    }
                @endphp
                <div class="flex justify-center">
                    @if($themesHere)
                        <flux:tooltip :content="$tooltipText">
                            <a href="{{ $href }}" class="{{ $cellClass }}">{{ $day }}</a>
                        </flux:tooltip>
                    @else
                        <span class="{{ $cellClass }}">{{ $day }}</span>
                    @endif
                </div>
            @endfor
        </div>
    </div>
</div>
