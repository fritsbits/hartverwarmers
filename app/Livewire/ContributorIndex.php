<?php

namespace App\Livewire;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ContributorIndex extends Component
{
    #[Url(as: 'zoek')]
    public string $search = '';

    public function isSearching(): bool
    {
        return strlen(trim($this->search)) >= 2;
    }

    /**
     * @return array{contributors_count: int, organisations_count: int, fiches_count: int}
     */
    #[Computed]
    public function stats(): array
    {
        return Cache::remember('contributors:stats', 3600, function () {
            $base = User::whereHas('fiches', fn ($q) => $q->published());

            return [
                'contributors_count' => (clone $base)->count(),
                'organisations_count' => (clone $base)->whereNotNull('organisation')->where('organisation', '!=', '')->distinct('organisation')->count('organisation'),
                'fiches_count' => Fiche::published()->count(),
            ];
        });
    }

    #[Computed]
    public function recentlyActive(): Collection
    {
        return Cache::remember('contributors:recently-active', 1800, function () {
            return User::query()
                ->whereHas('fiches', fn ($q) => $q->published())
                ->addSelect(['*', 'latest_fiche_at' => Fiche::query()
                    ->select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->published()
                    ->orderByDesc('created_at')
                    ->limit(1),
                ])
                ->with(['fiches' => fn ($q) => $q->published()
                    ->select('id', 'user_id', 'initiative_id', 'title', 'created_at')
                    ->orderByDesc('created_at')
                    ->limit(1)
                    ->with('initiative:id,title'),
                ])
                ->orderByDesc('latest_fiche_at')
                ->limit(4)
                ->get();
        });
    }

    #[Computed]
    public function newcomers(): Collection
    {
        return Cache::remember('contributors:newcomers', 1800, function () {
            $results = User::query()
                ->whereHas('fiches', fn ($q) => $q->published())
                ->withCount(['fiches as fiches_count' => fn ($q) => $q->published()])
                ->addSelect(['latest_fiche_at' => Fiche::query()
                    ->select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->published()
                    ->orderByDesc('created_at')
                    ->limit(1),
                ])
                ->with(['fiches' => fn ($q) => $q->published()
                    ->select('id', 'user_id', 'initiative_id', 'title', 'created_at')
                    ->orderByDesc('created_at')
                    ->limit(1)
                    ->with('initiative:id,title'),
                ])
                ->orderByDesc('latest_fiche_at')
                ->get()
                ->where('fiches_count', 1)
                ->take(4)
                ->values();

            return $results->count() >= 3 ? $results : collect();
        });
    }

    #[Computed]
    public function topContributors(): Collection
    {
        return Cache::remember('contributors:top', 3600, function () {
            return User::query()
                ->whereHas('fiches', fn ($q) => $q->published())
                ->withCount(['fiches as fiches_count' => fn ($q) => $q->published()])
                ->orderByDesc('fiches_count')
                ->limit(8)
                ->get();
        });
    }

    #[Computed]
    public function communityEngagers(): Collection
    {
        return Cache::remember('contributors:engagers', 1800, function () {
            $results = User::query()
                ->whereHas('fiches', fn ($q) => $q->published())
                ->withCount(['likes as kudos_given_count' => fn ($q) => $q->where('type', 'kudos')])
                ->withCount('comments')
                ->get()
                ->filter(fn ($u) => ($u->kudos_given_count + $u->comments_count) > 0)
                ->sortByDesc(fn ($u) => $u->kudos_given_count + $u->comments_count)
                ->take(5)
                ->values();

            return $results->count() >= 3 ? $results : collect();
        });
    }

    public function render(): View
    {
        $latestFicheSubquery = Fiche::query()
            ->select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->published()
            ->orderByDesc('created_at')
            ->limit(1);

        $query = User::query()
            ->whereHas('fiches', fn ($q) => $q->published())
            ->withCount(['fiches as fiches_count' => fn ($q) => $q->published()])
            ->addSelect(['latest_fiche_at' => $latestFicheSubquery])
            ->with(['fiches' => fn ($q) => $q->published()->select('id', 'user_id', 'initiative_id')->limit(3)->with('initiative:id,title')]);

        if ($this->isSearching()) {
            $term = trim($this->search);
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                ->orWhere('organisation', 'like', "%{$term}%"))
                ->orderByDesc('latest_fiche_at')
                ->orderBy('last_name');
        } else {
            $query->orderBy('first_name')->orderBy('last_name');
        }

        return view('livewire.contributor-index', [
            'contributors' => $query->get(),
        ]);
    }
}
