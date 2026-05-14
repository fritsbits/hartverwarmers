<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach($objectives as $obj)
        <a href="?tab={{ $obj->slug }}&range={{ $range }}" class="block">
            <flux:card class="hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-2">
                    <flux:heading size="lg" class="font-heading font-bold">{{ $obj->title }}</flux:heading>
                    <x-okr-status-badge :status="$obj->status" size="sm" />
                </div>
                @if($obj->description)
                    <p class="text-sm text-[var(--color-text-secondary)]">{{ $obj->description }}</p>
                @endif
            </flux:card>
        </a>
    @endforeach
</div>
