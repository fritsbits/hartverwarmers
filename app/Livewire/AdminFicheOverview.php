<?php

namespace App\Livewire;

use App\Jobs\AssessFicheQuality;
use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminFicheOverview extends Component
{
    use WithPagination;

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

    public ?int $ficheOfMonthId = null;

    public string $ficheOfMonthMonth = '';

    public function mount(): void
    {
        $this->ficheOfMonthMonth = now()->format('Y-m');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedInitiativeFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
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

    public function setFicheOfMonth(int $ficheId, string $month): void
    {
        // Raw update intentionally bypasses observers — no need to recalculate scores for metadata change
        Fiche::where('featured_month', $month)->update(['featured_month' => null]);
        Fiche::where('id', $ficheId)->update(['featured_month' => $month]);

        $this->ficheOfMonthId = null;
    }

    public function reassess(int $ficheId): void
    {
        $fiche = Fiche::findOrFail($ficheId);
        $fiche->updateQuietly(['quality_assessed_at' => null]);
        AssessFicheQuality::dispatch($fiche);
    }

    #[Computed]
    public function hasFicheOfMonth(): bool
    {
        return Fiche::where('featured_month', now()->format('Y-m'))->exists();
    }

    #[Computed]
    public function initiatives(): \Illuminate\Support\Collection
    {
        return Initiative::query()
            ->whereHas('fiches', fn ($q) => $q->published())
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    #[Computed]
    public function fiches(): LengthAwarePaginator
    {
        $allowedSorts = ['created_at', 'completeness_score', 'quality_score'];
        $sort = in_array($this->sortBy, $allowedSorts) ? $this->sortBy : 'created_at';

        $query = Fiche::query()
            ->published()
            ->with(['initiative', 'user'])
            ->withCount('files');

        if (strlen(trim($this->search)) >= 2) {
            $term = trim($this->search);
            $query->where('title', 'like', "%{$term}%");
        }

        match ($this->filter) {
            'unassessed' => $query->whereNull('quality_assessed_at'),
            'assessed' => $query->whereNotNull('quality_assessed_at'),
            'featured' => $query->whereNotNull('featured_month'),
            default => null,
        };

        if ($this->initiativeFilter !== '') {
            $query->where('initiative_id', (int) $this->initiativeFilter);
        }

        return $query->orderBy($sort, $this->sortDirection)->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin-fiche-overview');
    }
}
