<?php

namespace App\Livewire;

use App\Jobs\AssessFicheQuality;
use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminFicheOverview extends Component
{
    use WithPagination;

    private const QUADRANT_THRESHOLD = 50;

    private const QUADRANT_SORTS = [
        'q-strong' => [['quality_score', 'desc'], ['presentation_score', 'desc']],
        'q-quickwin' => [['presentation_score', 'asc'], ['quality_score', 'desc']],
        'q-wellwritten' => [['quality_score', 'asc'], ['presentation_score', 'desc']],
        'q-needswork' => [['quality_score', 'asc'], ['presentation_score', 'asc']],
    ];

    #[Url(as: 'zoek')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filter = '';

    #[Url(as: 'initiatief')]
    public string $initiativeFilter = '';

    #[Url(as: 'sorteer')]
    public string $sortBy = 'created_at';

    #[Url(as: 'richting')]
    public string $sortDirection = 'desc';

    public ?int $expandedFiche = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();

        if (isset(self::QUADRANT_SORTS[$this->filter])) {
            $primarySort = self::QUADRANT_SORTS[$this->filter][0];
            $this->sortBy = $primarySort[0];
            $this->sortDirection = $primarySort[1];
        }
    }

    public function updatedInitiativeFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function toggleExpanded(int $id): void
    {
        $this->expandedFiche = $this->expandedFiche === $id ? null : $id;
    }

    public function toggleDiamond(int $ficheId): void
    {
        $fiche = Fiche::findOrFail($ficheId);
        $awarding = ! $fiche->has_diamond;

        $fiche->updateQuietly([
            'has_diamond' => $awarding,
            'diamond_awarded_at' => $awarding ? now() : null,
        ]);

        Cache::forget('home:recent-diamond');
    }

    public function assess(int $ficheId): void
    {
        $fiche = Fiche::findOrFail($ficheId);
        $fiche->updateQuietly([
            'quality_score' => null,
            'quality_justification' => null,
            'quality_assessed_at' => null,
        ]);

        // Run synchronously so the result appears immediately in the UI
        (new AssessFicheQuality($fiche))->handle();
    }

    #[Computed]
    public function initiatives(): Collection
    {
        return Initiative::query()
            ->whereHas('fiches', fn ($q) => $q->published())
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    #[Computed]
    public function fiches(): LengthAwarePaginator
    {
        $allowedSorts = ['created_at', 'quality_score', 'presentation_score', 'combined_score'];
        $sort = in_array($this->sortBy, $allowedSorts) ? $this->sortBy : 'created_at';

        $query = Fiche::query()
            ->published()
            ->with(['initiative', 'user'])
            ->select('fiches.*')
            ->addSelect(DB::raw('(COALESCE(quality_score, 0) + COALESCE(presentation_score, 0)) as combined_score'));

        if (strlen(trim($this->search)) >= 2) {
            $term = trim($this->search);
            $query->where('title', 'like', "%{$term}%");
        }

        match ($this->filter) {
            'unassessed' => $query->whereNull('quality_assessed_at'),
            'assessed' => $query->whereNotNull('quality_assessed_at'),
            'q-strong' => $query->whereNotNull('quality_score')->whereNotNull('presentation_score')
                ->where('quality_score', '>=', self::QUADRANT_THRESHOLD)
                ->where('presentation_score', '>=', self::QUADRANT_THRESHOLD),
            'q-quickwin' => $query->whereNotNull('quality_score')->whereNotNull('presentation_score')
                ->where('quality_score', '>=', self::QUADRANT_THRESHOLD)
                ->where('presentation_score', '<', self::QUADRANT_THRESHOLD),
            'q-wellwritten' => $query->whereNotNull('quality_score')->whereNotNull('presentation_score')
                ->where('quality_score', '<', self::QUADRANT_THRESHOLD)
                ->where('presentation_score', '>=', self::QUADRANT_THRESHOLD),
            'q-needswork' => $query->whereNotNull('quality_score')->whereNotNull('presentation_score')
                ->where('quality_score', '<', self::QUADRANT_THRESHOLD)
                ->where('presentation_score', '<', self::QUADRANT_THRESHOLD),
            default => null,
        };

        if ($this->initiativeFilter !== '') {
            $query->where('initiative_id', (int) $this->initiativeFilter);
        }

        $quadrantSorts = self::QUADRANT_SORTS[$this->filter] ?? null;
        if ($quadrantSorts) {
            $query->orderBy($quadrantSorts[0][0], $quadrantSorts[0][1])
                ->orderBy($quadrantSorts[1][0], $quadrantSorts[1][1]);
        } else {
            $query->orderBy($sort, $this->sortDirection);
        }

        return $query->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin-fiche-overview');
    }
}
