<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminUserIndex extends Component
{
    use WithPagination;

    #[Url(as: 'zoek')]
    public string $search = '';

    #[Url(as: 'rol')]
    public string $role = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{total: int, admin: int, curator: int, contributor: int, member: int}
     */
    #[Computed]
    public function roleCounts(): array
    {
        return [
            'total' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'curator' => User::where('role', 'curator')->count(),
            'contributor' => User::where('role', 'contributor')->count(),
            'member' => User::where('role', 'member')->count(),
        ];
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query()
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'curator' THEN 2 WHEN 'contributor' THEN 3 WHEN 'member' THEN 4 ELSE 5 END")
            ->orderBy('first_name');

        if ($this->role) {
            $query->where('role', $this->role);
        }

        if (strlen(trim($this->search)) >= 2) {
            $term = trim($this->search);
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('organisation', 'like', "%{$term}%"));
        }

        return $query->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin-user-index');
    }
}
