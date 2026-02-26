<?php

namespace App\Livewire;

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

    public string $userInitial = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->existingAvatar = $user->avatar_path;
        $this->userInitial = substr($user->first_name, 0, 1);
    }

    public function updatedPhoto(): void
    {
        $this->validate();

        $user = auth()->user();

        // Delete old avatar
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->photo->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        $this->existingAvatar = $path;
        $this->photo = null;

        session()->flash('message', 'Profielfoto bijgewerkt.');
        $this->dispatch('avatar-updated');
    }

    public function deleteAvatar(): void
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->existingAvatar = null;

        session()->flash('message', 'Profielfoto verwijderd.');
        $this->dispatch('avatar-updated');
    }

    public function render()
    {
        return view('livewire.avatar-upload');
    }
}
