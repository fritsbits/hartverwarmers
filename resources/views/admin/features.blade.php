<x-sidebar-layout title="Feature Flags" section-label="Beheer" description="Schakel onderdelen van de applicatie in of uit.">

    <div class="space-y-4">
        @foreach($features as $feature)
            <div class="bg-white rounded-xl border border-[var(--color-border-light)] shadow-[var(--shadow-card)] p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-heading font-bold text-base">{{ $feature['label'] }}</h3>
                            @if($feature['active'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Actief
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Inactief
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $feature['description'] }}</p>
                    </div>

                    <form action="{{ route('admin.features.toggle', $feature['name']) }}" method="POST">
                        @csrf
                        @if($feature['active'])
                            <flux:button variant="ghost" type="submit" size="sm">Uitschakelen</flux:button>
                        @else
                            <flux:button variant="primary" type="submit" size="sm">Inschakelen</flux:button>
                        @endif
                    </form>
                </div>
            </div>
        @endforeach
    </div>

</x-sidebar-layout>
