@php
    $at = fn (?int $timestamp) => $timestamp
        ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp)->timezone('Europe/Brussels')->format('j M Y \o\m H:i')
        : '—';
    $gb = fn (int $bytes) => number_format($bytes / 1024 ** 3, 2, ',', '.').' GB';
    $runLabels = ['to-remote' => 'Kopie naar de bucket', 'to-local' => 'Herstel naar lokaal', 'cleanup' => 'Lokale opruiming'];
    $statusColors = ['verified' => 'green', 'done' => 'green', 'passed' => 'green', 'running' => 'blue', 'queued' => 'blue', 'planned' => 'blue', 'waiting' => 'amber', 'blocked' => 'red', 'failed' => 'red', 'stopped' => 'red'];
    $observed = $storage['observed'] ?? null;
    $smoke = $storage['smoketest'] ?? null;
    $drain = $storage['drain'] ?? null;
@endphp

<flux:card class="mt-6">
    <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1 mb-4">
        <flux:heading size="lg" class="font-heading font-bold">Fiche-opslag</flux:heading>
        <span class="text-xs text-[var(--color-text-tertiary)] tabular-nums">Release {{ $storage['release'] }}</span>
    </div>

    @if($storage['error'] ?? null)
        <div class="border-l-3 border-[var(--color-primary)] bg-[var(--color-primary)]/5 rounded-r-lg px-3 py-2.5 -mx-px">
            <p class="text-sm text-[var(--color-text-secondary)]">{{ $storage['error'] }}</p>
        </div>
    @else
        <div class="divide-y divide-[var(--color-border-light)]">
            <div class="flex items-center justify-between gap-3 py-2.5">
                <span class="text-sm text-[var(--color-text-secondary)]">Modus</span>
                <span class="text-sm font-medium tabular-nums">
                    {{ $storage['mode'] }}
                    @if($observed)
                        <span class="text-[var(--color-text-tertiary)] font-normal">(waargenomen sinds {{ $at($observed['since']) }})</span>
                    @endif
                </span>
            </div>

            @if($storage['mode'] !== 'local' || ($observed['previous'] ?? null))
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Oude processen uitgelopen</span>
                    <flux:badge size="sm" :color="($observed['drained_at'] ?? null) ? 'green' : 'amber'" inset="top bottom">
                        {{ ($observed['drained_at'] ?? null) ? 'Sinds '.$at($observed['drained_at']) : 'Nog niet' }}
                    </flux:badge>
                </div>
                @if(! ($observed['drained_at'] ?? null) && $drain && $drain['reasons'] !== [])
                    <ul class="text-xs text-[var(--color-text-tertiary)] py-2 list-disc pl-5">
                        @foreach($drain['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif

            <div class="flex items-center justify-between gap-3 py-2.5">
                <span class="text-sm text-[var(--color-text-secondary)]">Queue-workers</span>
                <span class="text-sm font-medium tabular-nums">{{ count($storage['workers'] ?? []) }}</span>
            </div>
            @foreach($storage['workers'] ?? [] as $pid => $worker)
                <p class="text-xs text-[var(--color-text-tertiary)] py-1.5 tabular-nums">Proces {{ $pid }} · release {{ $worker['release'] }} · modus {{ $worker['mode'] }} · {{ $at($worker['at']) }}</p>
            @endforeach

            <div class="flex items-center justify-between gap-3 py-2.5">
                <span class="text-sm text-[var(--color-text-secondary)]">Buckettest</span>
                <flux:badge size="sm" :color="$statusColors[$smoke['status'] ?? ''] ?? 'zinc'" inset="top bottom">{{ $smoke['status'] ?? 'nog niet gestart' }}</flux:badge>
            </div>
            @foreach(collect($smoke['checks'] ?? [])->reject(fn ($check) => $check['ok']) as $check)
                <p class="text-xs text-red-600 py-1.5">{{ $check['name'] }}: {{ $check['detail'] }}</p>
            @endforeach

            @foreach($storage['buckets'] ?? [] as $role => $bucket)
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Bucket {{ $role === 'files' ? 'bestanden' : 'previews' }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-[var(--color-text-tertiary)] tabular-nums">{{ $at($bucket['at']) }}</span>
                        <flux:badge size="sm" :color="$bucket['ok'] ? 'green' : 'red'" inset="top bottom">{{ $bucket['ok'] ? 'OK' : 'Onbereikbaar' }}</flux:badge>
                    </div>
                </div>
                @unless($bucket['ok'])
                    <p class="text-xs text-red-600 py-1.5">{{ $bucket['error'] }}</p>
                @endunless
            @endforeach

            @foreach($storage['runs'] ?? [] as $kind => $run)
                @continue($run === null)
                @php($summary = $run['summary'])
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">
                        {{ $runLabels[$kind] }}
                        <span class="text-[var(--color-text-tertiary)]">({{ $run['id'] }})</span>
                    </span>
                    <div class="flex items-center gap-2">
                        @if($kind === 'cleanup')
                            <span class="text-sm font-medium tabular-nums">
                                @if(isset($summary['deleted']))
                                    {{ $summary['deleted'] }} verwijderd · {{ $gb($summary['freed_bytes']) }} vrij · {{ $summary['skipped'] }} overgeslagen
                                @else
                                    {{ $summary['items'] ?? 0 }} gepland · {{ $gb($summary['bytes'] ?? 0) }}
                                @endif
                            </span>
                        @else
                            <span class="text-sm font-medium tabular-nums">
                                {{ $summary['states']['verified'] ?? 0 }} / {{ $summary['total'] ?? 0 }}
                                <span class="text-[var(--color-text-tertiary)] font-normal">· {{ $gb($summary['verified_bytes'] ?? 0) }} van {{ $gb($summary['bytes'] ?? 0) }} · ronde {{ $summary['round'] ?? 1 }}</span>
                            </span>
                        @endif
                        <flux:badge size="sm" :color="$statusColors[$run['status']] ?? 'zinc'" inset="top bottom">{{ $run['status'] }}</flux:badge>
                    </div>
                </div>
                @if($run['last_error'])
                    <p class="text-xs text-[var(--color-text-tertiary)] py-1.5">{{ $run['last_error'] }}</p>
                @endif
                @if(! empty($summary['missing']))
                    <p class="text-xs text-red-600 py-1.5">Ontbreekt: {{ implode(', ', $summary['missing']) }}</p>
                @endif
                @foreach($summary['failed'] ?? [] as $failure)
                    <p class="text-xs text-red-600 py-1.5">{{ $failure['path'] }}: {{ $failure['error'] }}</p>
                @endforeach
                @if(($summary['unknown'] ?? 0) > 0)
                    <p class="text-xs text-[var(--color-text-tertiary)] py-1.5">{{ $summary['unknown'] }} onbekend(e) lokaal(e) bestand(en) blijven staan, bv. {{ implode(', ', array_slice($summary['unknown_sample'] ?? [], 0, 5)) }}</p>
                @endif
            @endforeach

            @if($storage['mode'] !== 'local' || ($storage['runs']['to-remote'] ?? null))
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Objecten zonder geslaagde verificatie</span>
                    <span class="text-sm font-medium tabular-nums {{ ($storage['unverified'] ?? 0) > 0 ? 'text-amber-600' : '' }}">{{ $storage['unverified'] ?? 0 }}</span>
                </div>

                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Lokaal gelezen na omschakeling</span>
                    <span class="text-sm font-medium tabular-nums {{ ($storage['fallback_hits'] ?? 0) > 0 ? 'text-red-600' : '' }}">{{ $storage['fallback_hits'] ?? 0 }}</span>
                </div>
            @endif

            @if($storage['first_switch_at'] ?? null)
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Eerste omschakeling · vroegste opruiming</span>
                    <span class="text-sm font-medium tabular-nums">{{ $at($storage['first_switch_at']) }} · {{ $at($storage['earliest_cleanup_at']) }}</span>
                </div>
            @endif

            @if($storage['last_error'] ?? null)
                <div class="py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Laatste fout ({{ $at($storage['last_error']['at']) }})</span>
                    <p class="text-xs text-red-600 mt-1">
                        @if($storage['last_error']['path'])
                            {{ $storage['last_error']['role'] }}:{{ $storage['last_error']['path'] }} ·
                        @endif
                        {{ $storage['last_error']['message'] }}
                    </p>
                </div>
            @endif
        </div>
    @endif
</flux:card>
