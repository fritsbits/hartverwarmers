<div>
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Zoek op naam, e-mail of organisatie..." icon="magnifying-glass" clearable />
        </div>

        <flux:select wire:model.live="role" class="sm:w-44">
            <flux:select.option value="">Alle rollen ({{ $this->roleCounts['total'] }})</flux:select.option>
            <flux:select.option value="admin">Admin ({{ $this->roleCounts['admin'] }})</flux:select.option>
            <flux:select.option value="curator">Curator ({{ $this->roleCounts['curator'] }})</flux:select.option>
            <flux:select.option value="contributor">Bijdrager ({{ $this->roleCounts['contributor'] }})</flux:select.option>
            <flux:select.option value="member">Lid ({{ $this->roleCounts['member'] }})</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>Gebruiker</flux:table.column>
            <flux:table.column>Rol</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3 max-w-xs sm:max-w-sm md:max-w-md">
                            <x-user-avatar :user="$user" size="sm" class="shrink-0" />
                            <div class="min-w-0">
                                <span class="font-medium block truncate">{{ $user->full_name }}</span>
                                <span class="text-xs text-[var(--color-text-secondary)] block truncate">{{ $user->email }}@if($user->organisation) · {{ $user->organisation }}@endif</span>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" inset="top bottom" :color="match($user->role) {
                            'admin' => 'red',
                            'curator' => 'blue',
                            'contributor' => 'green',
                            default => 'zinc',
                        }">{{ $user->role }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.impersonate.start', $user) }}">
                                @csrf
                                <flux:button variant="ghost" size="xs" type="submit" icon="eye">Nabootsen</flux:button>
                            </form>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="text-center py-8">
                        <div class="text-[var(--color-text-secondary)]">
                            <flux:icon name="magnifying-glass" class="size-8 mx-auto mb-2 opacity-40" />
                            <p>Geen gebruikers gevonden</p>
                            @if($search || $role)
                                <p class="text-sm mt-1">Probeer andere zoektermen of filters.</p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
