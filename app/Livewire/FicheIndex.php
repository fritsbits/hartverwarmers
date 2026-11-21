<?php

namespace App\Livewire;

use App\Features\DiamantGoals;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Services\DiamantService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class FicheIndex extends Component
{
    public const PAGE_SIZE = 24;

    public const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maart', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Augustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'December',
    ];

    public const MONTHS_SHORT = [
        1 => 'jan', 2 => 'feb', 3 => 'mrt', 4 => 'apr', 5 => 'mei', 6 => 'jun',
        7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'dec',
    ];

    #[Url(as: 'q', history: false)]
    public string $q = '';

    /** @var array<int, string> */
    #[Url]
    public array $initiatief = [];

    /** @var array<int, string> */
    #[Url]
    public array $doel = [];

    #[Url]
    public bool $diamantjes = false;

    #[Url]
    public string $sort = 'nieuwste';

    public int $limit = self::PAGE_SIZE;

    public int $seed = 0;

    public function mount(): void
    {
        $this->seed = random_int(1, 2_000_000_000);
    }

    public function updated(string $name): void
    {
        if ($name !== 'limit') {
            $this->limit = self::PAGE_SIZE;
        }

        if ($name === 'sort' && $this->sort === 'verras') {
            $this->seed = random_int(1, 2_000_000_000);
        }
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function toggleSort(string $sort): void
    {
        $this->sort = $sort;
        $this->updated('sort');
    }

    public function removeFilter(string $group, string $value): void
    {
        if (in_array($group, ['initiatief', 'doel'], true)) {
            $this->{$group} = array_values(array_diff($this->{$group}, [$value]));
        }

        $this->limit = self::PAGE_SIZE;
    }

    public function clearFilters(): void
    {
        $this->reset('q', 'initiatief', 'doel', 'diamantjes');
        $this->limit = self::PAGE_SIZE;
    }

    public function isSearching(): bool
    {
        return mb_strlen(trim($this->q)) >= 2;
    }

    public function hasActiveFilters(): bool
    {
        return $this->isSearching()
            || $this->diamantjes
            || $this->activeInitiatives() !== []
            || $this->activeGoals() !== [];
    }

    #[Computed]
    public function goalsEnabled(): bool
    {
        return Feature::for(null)->active(DiamantGoals::class);
    }

    /**
     * @return array<string, array{slug: string, letter: string, keyword: string}>
     */
    #[Computed]
    public function goalFacets(): array
    {
        return collect(app(DiamantService::class)->all())
            ->mapWithKeys(fn (array $facet) => [$facet['slug'] => [
                'slug' => $facet['slug'],
                'letter' => $facet['letter'],
                'keyword' => $facet['keyword'],
            ]])
            ->all();
    }

    /**
     * @return Collection<int, Initiative>
     */
    #[Computed]
    public function initiatives(): Collection
    {
        return Initiative::query()->published()->orderBy('title')->get(['id', 'title', 'slug']);
    }

    /**
     * @return array<int, string>
     */
    protected function activeInitiatives(): array
    {
        $known = $this->initiatives->pluck('slug')->all();

        return array_values(array_intersect($this->initiatief, $known));
    }

    /**
     * @return array<int, string>
     */
    protected function activeGoals(): array
    {
        if (! $this->goalsEnabled) {
            return [];
        }

        return array_values(array_intersect($this->doel, array_keys($this->goalFacets)));
    }

    /**
     * Visible fiches with every active filter applied except the skipped group.
     *
     * @return Builder<Fiche>
     */
    protected function filteredQuery(?string $skip = null): Builder
    {
        $query = Fiche::query()
            ->published()
            ->whereHas('initiative', fn ($q) => $q->published());

        if ($this->isSearching()) {
            foreach (preg_split('/\s+/', trim($this->q)) as $word) {
                $like = '%'.addcslashes($word, '%_\\').'%';
                $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like));
            }
        }

        if ($this->diamantjes) {
            $query->where('has_diamond', true);
        }

        if ($skip !== 'initiatief' && $this->activeInitiatives() !== []) {
            $query->whereHas('initiative', fn ($q) => $q->whereIn('slug', $this->activeInitiatives()));
        }

        if ($skip !== 'doel' && $this->activeGoals() !== []) {
            $tagSlugs = array_map(fn (string $slug) => 'doel-'.$slug, $this->activeGoals());
            $query->whereHas('tags', fn ($q) => $q->where('type', 'goal')->whereIn('slug', $tagSlugs));
        }

        return $query;
    }

    /**
     * @return array<int, array{slug: string, title: string, count: int}>
     */
    #[Computed]
    public function initiativeOptions(): array
    {
        $counts = $this->filteredQuery('initiatief')
            ->reorder()
            ->selectRaw('initiative_id, count(*) as aggregate')
            ->groupBy('initiative_id')
            ->pluck('aggregate', 'initiative_id');

        return $this->initiatives
            ->map(fn (Initiative $initiative) => [
                'slug' => $initiative->slug,
                'title' => $initiative->title,
                'count' => (int) ($counts[$initiative->id] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<int, array{slug: string, letter: string, keyword: string, count: int}>
     */
    #[Computed]
    public function goalOptions(): array
    {
        $ids = $this->filteredQuery('doel')->reorder()->toBase()->pluck('fiches.id');

        $counts = DB::table('taggables')
            ->join('tags', 'tags.id', '=', 'taggables.tag_id')
            ->where('taggables.taggable_type', (new Fiche)->getMorphClass())
            ->where('tags.type', 'goal')
            ->whereIn('taggables.taggable_id', $ids)
            ->selectRaw('tags.slug, count(distinct taggables.taggable_id) as aggregate')
            ->groupBy('tags.slug')
            ->pluck('aggregate', 'slug');

        return collect($this->goalFacets)
            ->map(fn (array $facet) => $facet + ['count' => (int) ($counts['doel-'.$facet['slug']] ?? 0)])
            ->values()
            ->all();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Fiche::query()->published()->whereHas('initiative', fn ($q) => $q->published())->count();
    }

    /**
     * Ordered ids of every fiche matching the filters.
     *
     * @return Collection<int, int>
     */
    #[Computed]
    public function matchingIds(): Collection
    {
        $query = $this->filteredQuery();

        if ($this->sort === 'bedankt') {
            $query->orderByDesc('kudos_count')->orderByDesc('created_at')->orderByDesc('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $ids = $query->toBase()->pluck('fiches.id');

        return $this->sort === 'verras' ? $ids->sortBy(fn (int $id) => crc32($this->seed.'-'.$id))->values() : $ids;
    }

    #[Computed]
    public function resultCount(): int
    {
        return $this->matchingIds->count();
    }

    public function isGrouped(): bool
    {
        return $this->sort === 'nieuwste' && $this->resultCount > self::PAGE_SIZE;
    }

    /**
     * @return Collection<int, Fiche>
     */
    #[Computed]
    public function fiches(): Collection
    {
        $ids = $this->matchingIds->take($this->limit);

        $byId = Fiche::query()
            ->with(['initiative:id,title,slug', 'user'])
            ->withCount('comments')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $ids->map(fn (int $id) => $byId[$id])->values();
    }

    /**
     * Full result count per month for the current filters.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function monthCounts(): array
    {
        return $this->filteredQuery()
            ->reorder()
            ->toBase()
            ->pluck('created_at')
            ->countBy(fn (string $date) => substr($date, 0, 7))
            ->all();
    }

    public function monthLabel(CarbonInterface $date): string
    {
        return self::MONTHS[$date->month].' '.$date->year;
    }

    public function dateLabel(CarbonInterface $date, bool $withYear): string
    {
        return $date->day.' '.self::MONTHS_SHORT[$date->month].($withYear ? ' '.$date->year : '');
    }

    public function render(): View
    {
        return view('livewire.fiche-index');
    }
}
