<a href="{{ route('contributors.show', $contributor) }}"
   class="group flex flex-col items-center text-center p-4 rounded-2xl hover:bg-[var(--color-bg-cream)] transition-colors">

    {{-- Avatar --}}
    @if($contributor->avatar_path)
        <img src="{{ $contributor->avatarUrl() }}"
             alt=""
             class="w-14 h-14 rounded-full object-cover mb-3"
             loading="lazy">
    @else
        <div class="w-14 h-14 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center text-lg font-bold mb-3">
            {{ mb_substr($contributor->first_name, 0, 1) }}
        </div>
    @endif

    {{-- Name --}}
    <h3 class="font-heading font-bold text-sm leading-snug group-hover:text-[var(--color-primary)] transition-colors truncate max-w-full">
        {{ $contributor->full_name }}
    </h3>

    {{-- Organisation --}}
    @if($contributor->organisation)
        <p class="text-xs text-[var(--color-text-secondary)] font-light truncate max-w-full mt-0.5">
            {{ $contributor->organisation }}
        </p>
    @endif

    {{-- Metric --}}
    @if($metric)
        <p class="text-xs text-[var(--color-text-secondary)] font-light mt-1">
            {{ $metric }}
        </p>
    @endif
</a>
