<?php

namespace App\Livewire;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ContributorIndex extends Component
{
    use WithPagination;

    #[Url(as: 'zoek')]
    public string $search = '';

    /**
     * @return array{contributors_count: int, organisations_count: int, fiches_count: int}
     */
    #[Computed]
    public function stats(): array
    {
        return Cache::remember('contributors:stats', 3600, function () {
            return [
                'contributors_count' => User::has('fiches')->count(),
                'organisations_count' => User::has('fiches')->whereNotNull('organisation')->where('organisation', '!=', '')->distinct('organisation')->count('organisation'),
                'fiches_count' => Fiche::published()->count(),
            ];
        });
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = User::query()
            ->has('fiches')
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->with(['fiches' => fn ($q) => $q->published()->with('initiative:id,title')->select('id', 'user_id', 'initiative_id')])
            ->orderByDesc('fiches_count')
            ->orderBy('last_name');

        if (strlen(trim($this->search)) >= 2) {
            $term = trim($this->search);
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                ->orWhere('organisation', 'like', "%{$term}%"));
        }

        return view('livewire.contributor-index', [
            'contributors' => $query->simplePaginate(24),
        ]);
    }
}
