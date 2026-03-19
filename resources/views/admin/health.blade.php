@php
    use App\Services\ServerHealth;

    $badgeColor = fn (string $status) => match ($status) {
        'red' => 'red',
        'amber' => 'yellow',
        default => 'green',
    };

    $badgeLabel = fn (string $status) => match ($status) {
        'red' => 'Kritiek',
        'amber' => 'Waarschuwing',
        default => 'OK',
    };
@endphp

<x-sidebar-layout title="Gezondheid" section-label="Beheer" description="Serverstatus en systeemgezondheid.">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Server card --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-4">Server</flux:heading>

            <div class="space-y-3">
                @if($memory)
                    @php $memStatus = ServerHealth::statusForValue($memory['percent'], 'memory_percent'); @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-[var(--color-text-secondary)]">Geheugen</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ ServerHealth::formatBytes($memory['used']) }} / {{ ServerHealth::formatBytes($memory['total']) }} ({{ $memory['percent'] }}%)</span>
                            <flux:badge size="sm" :color="$badgeColor($memStatus)" inset="top bottom">{{ $badgeLabel($memStatus) }}</flux:badge>
                        </div>
                    </div>
                @endif

                @php $diskStatus = ServerHealth::statusForValue($disk['percent'], 'disk_percent'); @endphp
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--color-text-secondary)]">Schijf</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ ServerHealth::formatBytes($disk['used']) }} / {{ ServerHealth::formatBytes($disk['total']) }} ({{ $disk['percent'] }}%)</span>
                        <flux:badge size="sm" :color="$badgeColor($diskStatus)" inset="top bottom">{{ $badgeLabel($diskStatus) }}</flux:badge>
                    </div>
                </div>

                @if($load)
                    @php $loadStatus = ServerHealth::statusForValue($load['1m'], 'load_average'); @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-[var(--color-text-secondary)]">Load (1m / 5m / 15m)</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ $load['1m'] }} / {{ $load['5m'] }} / {{ $load['15m'] }}</span>
                            <flux:badge size="sm" :color="$badgeColor($loadStatus)" inset="top bottom">{{ $badgeLabel($loadStatus) }}</flux:badge>
                        </div>
                    </div>
                @endif

                @if($uptime)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-[var(--color-text-secondary)]">Uptime</span>
                        <span class="text-sm font-medium">{{ $uptime }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--color-text-secondary)]">PHP / Laravel</span>
                    <span class="text-sm font-medium">{{ $phpVersion }} / {{ $laravelVersion }}</span>
                </div>
            </div>
        </flux:card>

        {{-- Queue card --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-4">Queue</flux:heading>

            <div class="space-y-3">
                @php
                    $heartbeatOk = $queue['heartbeat_age'] !== null && $queue['heartbeat_age'] < 600;
                    $heartbeatLabel = match(true) {
                        $queue['heartbeat_age'] === null => 'Nooit',
                        $queue['heartbeat_age'] < 60 => $queue['heartbeat_age'] . 's geleden',
                        $queue['heartbeat_age'] < 3600 => round($queue['heartbeat_age'] / 60) . ' min geleden',
                        default => round($queue['heartbeat_age'] / 3600, 1) . ' uur geleden',
                    };
                @endphp

                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--color-text-secondary)]">Heartbeat</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ $heartbeatLabel }}</span>
                        <flux:badge size="sm" :color="$heartbeatOk ? 'green' : 'red'" inset="top bottom">{{ $heartbeatOk ? 'OK' : 'Stalled' }}</flux:badge>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--color-text-secondary)]">Wachtend</span>
                    <span class="text-sm font-medium">{{ number_format($queue['pending']) }} jobs</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--color-text-secondary)]">Mislukt</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ number_format($queue['failed']) }} jobs</span>
                        @if($queue['failed'] > 0)
                            <flux:badge size="sm" color="red" inset="top bottom">!</flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

    </div>

    {{-- Errors card (full width) --}}
    <flux:card class="mt-6">
        <flux:heading size="lg" class="font-heading font-bold mb-4">Recente fouten</flux:heading>

        @if($errors->isEmpty())
            <p class="text-sm text-[var(--color-text-secondary)]">Geen recente fouten.</p>
        @else
            <div class="space-y-2">
                @foreach($errors as $error)
                    <div class="flex items-start gap-3 text-sm">
                        <span class="shrink-0 text-[var(--color-text-tertiary)] tabular-nums">{{ $error['date'] }}</span>
                        <flux:badge size="sm" color="{{ $error['level'] === 'ERROR' ? 'red' : 'orange' }}" inset="top bottom" class="shrink-0">{{ $error['level'] }}</flux:badge>
                        <span class="text-[var(--color-text-secondary)] truncate">{{ $error['message'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

</x-sidebar-layout>
