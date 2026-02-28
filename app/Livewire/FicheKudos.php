<?php

namespace App\Livewire;

use App\Models\Fiche;
use App\Models\Like;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FicheKudos extends Component
{
    public const MAX_KUDOS_PER_USER = 25;

    public Fiche $fiche;

    #[Computed]
    public function isOwnFiche(): bool
    {
        return auth()->id() === $this->fiche->user_id;
    }

    #[Computed]
    public function maxKudos(): int
    {
        return self::MAX_KUDOS_PER_USER;
    }

    public function addKudos(int $amount): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        if (auth()->id() === $this->fiche->user_id) {
            return;
        }

        $amount = max(1, min($amount, self::MAX_KUDOS_PER_USER));

        $kudos = Like::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'likeable_type' => Fiche::class,
                'likeable_id' => $this->fiche->id,
                'type' => 'kudos',
            ],
            ['count' => 0]
        );

        $remaining = self::MAX_KUDOS_PER_USER - $kudos->count;
        if ($remaining <= 0) {
            unset($this->totalKudos, $this->myKudos, $this->kudosGiversCount);

            return;
        }

        $kudos->increment('count', min($amount, $remaining));

        unset($this->totalKudos, $this->myKudos, $this->kudosGiversCount);
    }

    public function toggleBookmark(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        $bookmark = Like::where('user_id', auth()->id())
            ->where('likeable_type', Fiche::class)
            ->where('likeable_id', $this->fiche->id)
            ->where('type', 'bookmark')
            ->first();

        if ($bookmark) {
            $bookmark->delete();
        } else {
            Like::create([
                'user_id' => auth()->id(),
                'likeable_type' => Fiche::class,
                'likeable_id' => $this->fiche->id,
                'type' => 'bookmark',
            ]);
        }
    }

    #[Computed]
    public function totalKudos(): int
    {
        return (int) $this->fiche->kudos()->sum('count');
    }

    #[Computed]
    public function myKudos(): int
    {
        if (! auth()->check()) {
            return 0;
        }

        return (int) Like::where('user_id', auth()->id())
            ->where('likeable_type', Fiche::class)
            ->where('likeable_id', $this->fiche->id)
            ->where('type', 'kudos')
            ->value('count') ?? 0;
    }

    #[Computed]
    public function isBookmarked(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return Like::where('user_id', auth()->id())
            ->where('likeable_type', Fiche::class)
            ->where('likeable_id', $this->fiche->id)
            ->where('type', 'bookmark')
            ->exists();
    }

    #[Computed]
    public function recentKudosUsers()
    {
        return Like::where('likeable_type', Fiche::class)
            ->where('likeable_id', $this->fiche->id)
            ->where('type', 'kudos')
            ->where('count', '>', 0)
            ->with('user')
            ->latest()
            ->limit(5)
            ->get()
            ->pluck('user')
            ->filter();
    }

    #[Computed]
    public function kudosGiversCount(): int
    {
        return Like::where('likeable_type', Fiche::class)
            ->where('likeable_id', $this->fiche->id)
            ->where('type', 'kudos')
            ->where('count', '>', 0)
            ->count();
    }

    public function render()
    {
        return view('livewire.fiche-kudos');
    }
}
