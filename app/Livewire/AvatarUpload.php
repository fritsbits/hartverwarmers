<?php

namespace App\Livewire;

use App\Services\AvatarThumbnailService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class AvatarUpload extends Component
{
    use WithFileUploads;

    #[Validate('nullable|image|mimes:jpeg,png,webp|max:2048')]
    public $photo;

    public ?string $existingAvatar = null;

    public ?string $existingAvatarUrl = null;

    public string $initials = '';

    public int $colorIndex = 0;

    public function mount(): void
    {
        $user = auth()->user();
        $this->existingAvatar = $user->avatar_path;
        $this->existingAvatarUrl = $user->avatarUrl();
        $this->initials = mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1));
        $this->colorIndex = $user->id % 4;
    }

    public function updatedPhoto(): void
    {
        $this->validate();

        $user = auth()->user();
        $thumbnailService = app(AvatarThumbnailService::class);

        // Delete old avatar and its thumbnail
        if ($user->avatar_path) {
            $thumbnailService->delete($user->avatar_path);
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->photo->store('avatars', 'public');
        $thumbnailService->generate($path);
        $user->update(['avatar_path' => $path]);

        $this->existingAvatar = $path;
        $this->existingAvatarUrl = $user->avatarUrl();
        $this->photo = null;

        Flux::toast('Profielfoto bijgewerkt.', variant: 'success');
        $this->dispatch('avatar-updated');
    }

    public function deleteAvatar(): void
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            app(AvatarThumbnailService::class)->delete($user->avatar_path);
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->existingAvatar = null;
        $this->existingAvatarUrl = null;

        Flux::toast('Profielfoto verwijderd.', variant: 'success');
        $this->dispatch('avatar-updated');
    }

    public function render()
    {
        return view('livewire.avatar-upload');
    }
}
