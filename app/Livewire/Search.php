<?php

namespace App\Livewire;

use App\Models\Fiche;
use App\Models\Initiative;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Search extends Component
{
    public string $query = '';

    #[Computed]
    public function results(): array
    {
        if (strlen(trim($this->query)) < 2) {
            return ['initiatives' => collect(), 'fiches' => collect()];
        }

        $term = trim($this->query);

        $initiatives = Initiative::published()
            ->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"))
            ->limit(5)
            ->get(['id', 'title', 'slug', 'description']);

        $fiches = Fiche::published()
            ->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"))
            ->with('initiative:id,slug')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'description', 'initiative_id']);

        return ['initiatives' => $initiatives, 'fiches' => $fiches];
    }

    #[Computed]
    public function hasResults(): bool
    {
        $results = $this->results;

        return $results['initiatives']->isNotEmpty() || $results['fiches']->isNotEmpty();
    }

    public function render()
    {
        return view('livewire.search');
    }
}
