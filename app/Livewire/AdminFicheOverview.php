<?php

namespace App\Livewire;

use App\Jobs\AssessFicheQuality;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Services\FichePurger;
use Flux\Flux;
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

    /**
     * Typed literally before a purge goes through. Uppercase on purpose: a
     * purge is irreversible and used for takedown requests, so it should not
     * be reachable by muscle memory.
     */
    private const PURGE_CONFIRMATION = 'VERWIJDER';

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

    public bool $showPurgeModal = false;

    public ?int $purgingFiche = null;

    public string $purgeConfirmation = '';

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

    public function confirmPurge(int $ficheId): void
    {
        $this->authorizeAdmin();

        $this->purgingFiche = $ficheId;
        $this->purgeConfirmation = '';
        $this->resetErrorBag();
        $this->showPurgeModal = true;

        unset($this->purgeTarget);
    }

    /**
     * Permanently remove a fiche, its files and every trace of it. Used for
     * copyright takedowns, where a soft delete would leave the file
     * downloadable under /storage.
     */
    public function purge(FichePurger $purger): void
    {
        $this->authorizeAdmin();

        if ($this->purgeConfirmation !== self::PURGE_CONFIRMATION) {
            $this->addError('purgeConfirmation', 'Typ '.self::PURGE_CONFIRMATION.' in hoofdletters om te bevestigen.');

            return;
        }

        $fiche = $this->purgeTarget;

        if (! $fiche) {
            $this->closePurgeModal();

            return;
        }

        $title = $fiche->title;
        $summary = $purger->purge($fiche);

        $this->closePurgeModal();
        $this->expandedFiche = null;
        $this->resetPage();

        Cache::forget('home:recent-diamond');
        Cache::forget('footer_stats');

        Flux::toast(
            "\"{$title}\" is definitief verwijderd, samen met {$summary['files']} bestand(en) en {$summary['images']} afbeelding(en).",
            variant: 'success',
        );
    }

    private function closePurgeModal(): void
    {
        $this->showPurgeModal = false;
        $this->purgingFiche = null;
        $this->purgeConfirmation = '';

        unset($this->purgeTarget);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function purgeTarget(): ?Fiche
    {
        if ($this->purgingFiche === null) {
            return null;
        }

        return Fiche::query()
            ->with(['initiative', 'files', 'user' => fn ($q) => $q->withTrashed()])
            ->withCount(['comments', 'likes'])
            ->find($this->purgingFiche);
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
            ->when(
                $this->filter === 'unpublished',
                fn ($q) => $q->where('published', false),
                fn ($q) => $q->published(),
            )
            ->with(['initiative', 'user' => fn ($q) => $q->withTrashed()])
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
