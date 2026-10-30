<?php

namespace App\Support\MilestoneCall;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Support\Collection;

final readonly class MilestoneCallContent
{
    /**
     * @param  Collection<int, Initiative>  $richest
     * @param  Collection<int, Initiative>  $needsLove
     */
    public function __construct(
        public int $fichesCount,
        public int $target,
        public Collection $richest,
        public Collection $needsLove,
    ) {}

    /**
     * Counts follow the initiatives page: published initiatives, published
     * fiches. The thin end is "the fewest", not a fixed threshold, so the
     * list stays four lines long however the counts grow.
     */
    public static function build(): self
    {
        $initiatives = Initiative::query()
            ->published()
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->get();

        return new self(
            fichesCount: Fiche::published()->count(),
            target: (int) config('newsletter.milestone_call_target'),
            richest: $initiatives->sortByDesc('fiches_count')->take(3)->values(),
            needsLove: $initiatives->sortBy([['fiches_count', 'asc'], ['title', 'asc']])->take(4)->values(),
        );
    }

    public function remaining(): int
    {
        return max(0, $this->target - $this->fichesCount);
    }

    public function reached(): bool
    {
        return $this->remaining() === 0;
    }
}
