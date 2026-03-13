<?php

namespace App\Livewire;

use App\Models\Like;
use App\Models\Tag;
use App\Services\DiamantService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OnboardingBanner extends Component
{
    public ?int $level = null;

    public function mount(): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->onboarded_at === null) {
            $this->level = 1;
        } elseif ($user->contributor_onboarded_at === null && $user->fiches()->published()->exists()) {
            if ($user->avatar_path && $user->bio && $user->function_title && $user->organisation) {
                $user->update(['contributor_onboarded_at' => now()]);
            } else {
                $this->level = 2;
            }
        }
    }

    public function dismiss(): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($this->level === 1) {
            $user->update(['onboarded_at' => now()]);
        } elseif ($this->level === 2) {
            $user->update(['contributor_onboarded_at' => now()]);
        }

        $this->level = null;
    }

    #[Computed]
    public function sessionKudosCount(): int
    {
        if (! auth()->check()) {
            return 0;
        }

        return (int) Like::where('user_id', auth()->id())
            ->where('type', 'kudos')
            ->sum('count');
    }

    #[Computed]
    public function underservedGoals(): array
    {
        $diamant = app(DiamantService::class);
        $facets = collect($diamant->all());

        $goalTags = Tag::query()
            ->where('type', 'goal')
            ->where('slug', 'like', 'doel-%')
            ->get()
            ->keyBy('slug');

        $ficheCounts = [];
        foreach ($goalTags as $slug => $tag) {
            $ficheCounts[$slug] = $tag->fiches()->published()->count();
        }

        asort($ficheCounts);

        return collect($ficheCounts)
            ->take(2)
            ->map(function ($count, $slug) use ($facets) {
                $facetSlug = str_replace('doel-', '', $slug);
                $facet = $facets->firstWhere('slug', $facetSlug);

                return [
                    'keyword' => $facet ? $facet['keyword'] : $facetSlug,
                    'slug' => $facetSlug,
                    'fiche_count' => $count,
                ];
            })
            ->values()
            ->all();
    }

    #[Computed]
    public function profileCompleteness(): array
    {
        if (! auth()->check()) {
            return [];
        }

        $user = auth()->user();
        $missing = [];

        if (! $user->avatar_path) {
            $missing[] = 'avatar';
        }
        if (! $user->bio) {
            $missing[] = 'bio';
        }
        if (! $user->function_title || ! $user->organisation) {
            $missing[] = 'function';
        }

        return $missing;
    }

    public function render()
    {
        return view('livewire.onboarding-banner');
    }
}
