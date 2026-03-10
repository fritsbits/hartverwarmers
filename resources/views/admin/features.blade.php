<x-sidebar-layout title="Feature Flags" section-label="Beheer" description="Schakel onderdelen van de applicatie in of uit.">

    <div class="space-y-4">
        @foreach($features as $feature)
            <flux:card>
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-heading font-bold text-base">{{ $feature['label'] }}</span>
                            @if($feature['active'])
                                <flux:badge size="sm" color="green" inset="top bottom">Actief</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" inset="top bottom">Inactief</flux:badge>
                            @endif
                        </div>
                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $feature['description'] }}</p>
                    </div>

                    <form action="{{ route('admin.features.toggle', $feature['name']) }}" method="POST" class="shrink-0"
                          onsubmit="return confirm('{{ $feature['active'] ? 'Weet je zeker dat je \'' . $feature['label'] . '\' wilt uitschakelen?' : 'Weet je zeker dat je \'' . $feature['label'] . '\' wilt inschakelen?' }}')">
                        @csrf
                        @if($feature['active'])
                            <flux:button variant="ghost" type="submit" size="sm">Uitschakelen</flux:button>
                        @else
                            <flux:button variant="primary" type="submit" size="sm">Inschakelen</flux:button>
                        @endif
                    </form>
                </div>
            </flux:card>
        @endforeach
    </div>

</x-sidebar-layout>
