<?php

namespace App\Livewire;

use App\Livewire\Concerns\CreatesGuestAccount;
use App\Models\Fiche;
use App\Models\Like;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FicheKudos extends Component
{
    use CreatesGuestAccount;

    public const MAX_KUDOS_PER_USER = 25;

    public const MAX_KUDOS_PER_SESSION = 10;

    public Fiche $fiche;

    public bool $showBookmarkAuth = false;

    #[Computed]
    public function isOwnFiche(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->id() === $this->fiche->user_id;
    }

    #[Computed]
    public function maxKudos(): int
    {
        return auth()->check() ? self::MAX_KUDOS_PER_USER : self::MAX_KUDOS_PER_SESSION;
    }

    public function addKudos(int $amount): void
    {
        if (auth()->check() && auth()->id() === $this->fiche->user_id) {
            return;
        }

        $max = auth()->check() ? self::MAX_KUDOS_PER_USER : self::MAX_KUDOS_PER_SESSION;
        $amount = max(1, min($amount, $max));

        if (auth()->check()) {
            $kudos = Like::firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'likeable_type' => Fiche::class,
                    'likeable_id' => $this->fiche->id,
                    'type' => 'kudos',
                ],
                ['count' => 0]
            );
        } else {
            $kudos = Like::firstOrCreate(
                [
                    'session_id' => session()->getId(),
                    'user_id' => null,
                    'likeable_type' => Fiche::class,
                    'likeable_id' => $this->fiche->id,
                    'type' => 'kudos',
                ],
                ['count' => 0]
            );
        }

        $remaining = $max - $kudos->count;
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
            $this->showBookmarkAuth = true;

            return;
        }

        $this->performBookmarkToggle();
    }

    public function guestBookmark(): void
    {
        $this->validateGuestIdentity();
        $user = $this->createGuestUser();
        $this->showBookmarkAuth = false;

        $this->performBookmarkToggle();

        unset($this->isBookmarked);

        $this->dispatch('guest-welcome', name: $user->first_name);
    }

    public function cancelBookmarkAuth(): void
    {
        $this->showBookmarkAuth = false;
    }

    private function performBookmarkToggle(): void
    {
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
        if (auth()->check()) {
            return (int) Like::where('user_id', auth()->id())
                ->where('likeable_type', Fiche::class)
                ->where('likeable_id', $this->fiche->id)
                ->where('type', 'kudos')
                ->value('count') ?? 0;
        }

        return (int) Like::where('session_id', session()->getId())
            ->whereNull('user_id')
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
