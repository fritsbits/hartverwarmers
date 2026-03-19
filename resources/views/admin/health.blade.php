@php
    use App\Services\ServerHealth;

    // Compute statuses
    $memStatus = $memory ? ServerHealth::statusForValue($memory['percent'], 'memory_percent') : 'green';
    $diskStatus = ServerHealth::statusForValue($disk['percent'], 'disk_percent');
    $loadStatus = $load ? ServerHealth::statusForValue($load['1m'], 'load_average') : 'green';
    $heartbeatOk = $queue['heartbeat_age'] !== null && $queue['heartbeat_age'] < 600;

    // Collect problems for status banner
    $problems = collect();
    if ($memStatus === 'red') $problems->push('Geheugen bijna vol');
    if ($memStatus === 'amber') $problems->push('Geheugen raakt vol');
    if ($diskStatus === 'red') $problems->push('Schijf bijna vol');
    if ($diskStatus === 'amber') $problems->push('Schijf raakt vol');
    if ($loadStatus === 'red') $problems->push('Serverbelasting te hoog');
    if ($loadStatus === 'amber') $problems->push('Server is druk');
    if (! $heartbeatOk) $problems->push('Achtergrondtaken gestopt');
    if ($queue['failed'] > 0) $problems->push($queue['failed'] . ' mislukte ' . ($queue['failed'] === 1 ? 'taak' : 'taken'));

    $hasProblems = $problems->isNotEmpty();
    $hasCritical = in_array('red', [$memStatus, $diskStatus, $loadStatus]) || ! $heartbeatOk;

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

    // Build heartbeat label
    $heartbeatLabel = match(true) {
        $queue['heartbeat_age'] === null => 'Geen signaal',
        $queue['heartbeat_age'] < 60 => $queue['heartbeat_age'] . 's geleden',
        $queue['heartbeat_age'] < 3600 => round($queue['heartbeat_age'] / 60) . ' min geleden',
        default => round($queue['heartbeat_age'] / 3600, 1) . ' uur geleden',
    };

    // Build AI prompt
    $promptLines = [
        "Ik ben de beheerder van Hartverwarmers, een Laravel-applicatie op een DigitalOcean-droplet (2 GB RAM, 1 core) beheerd via Laravel Forge.",
        "",
        "Hieronder staat de huidige serverstatus. Help me begrijpen wat er aan de hand is en zoek oplossingen. Leg het eenvoudig uit — ik ben geen systeembeheerder. Begin met het identificeren van de oorzaak en stel dan concrete stappen voor om het op te lossen.",
        "",
        "## Serverstatus",
    ];
    if ($memory) {
        $promptLines[] = "- Geheugen: " . ServerHealth::formatBytes($memory['used']) . " / " . ServerHealth::formatBytes($memory['total']) . " ({$memory['percent']}%) — " . $badgeLabel($memStatus);
    }
    $promptLines[] = "- Schijf: " . ServerHealth::formatBytes($disk['used']) . " / " . ServerHealth::formatBytes($disk['total']) . " ({$disk['percent']}%) — " . $badgeLabel($diskStatus);
    if ($load) {
        $promptLines[] = "- Serverbelasting: " . ServerHealth::loadLabel($load['1m']) . " (raw: {$load['1m']} / {$load['5m']} / {$load['15m']}) — " . $badgeLabel($loadStatus);
    }
    $promptLines[] = "- Achtergrondtaken (queue worker): " . ($heartbeatOk ? "OK ({$heartbeatLabel})" : "Gestopt ({$heartbeatLabel})");
    $promptLines[] = "- Wachtende jobs: {$queue['pending']}";
    $promptLines[] = "- Mislukte jobs: {$queue['failed']}";
    if ($errors->isNotEmpty()) {
        $promptLines[] = "";
        $promptLines[] = "## Recente fouten";
        foreach ($errors as $error) {
            $countLabel = $error['count'] > 1 ? " (x{$error['count']})" : "";
            $promptLines[] = "- [{$error['date']}] {$error['level']}: {$error['message']}{$countLabel}";
        }
    }
    $aiPrompt = implode("\n", $promptLines);
@endphp

<x-sidebar-layout title="Gezondheid" section-label="Beheer" description="Serverstatus en systeemgezondheid.">

    {{-- Page header with AI button --}}
    <div class="flex items-center justify-between -mt-6 mb-6">
        <div></div>
        <button
            onclick="navigator.clipboard.writeText(document.getElementById('ai-prompt').textContent).then(() => { const span = this.querySelector('span'); span.textContent = 'Gekopieerd!'; setTimeout(() => { span.textContent = 'Maak prompt voor AI'; }, 2000); })"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)] bg-white border border-[var(--color-border-light)] rounded-lg px-3 py-1.5 hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors cursor-pointer"
        >
            <flux:icon name="clipboard" variant="mini" class="size-4" />
            <span>Maak prompt voor AI</span>
        </button>
        <div id="ai-prompt" class="hidden">{{ $aiPrompt }}</div>
    </div>

    {{-- Status banner --}}
    @if($hasProblems)
        <div class="flex items-center gap-3 rounded-xl px-4 py-3 mb-6 {{ $hasCritical ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-800' }}">
            <flux:icon name="exclamation-triangle" class="size-5 shrink-0" />
            <div>
                <strong>{{ $problems->count() }} {{ $problems->count() === 1 ? 'aandachtspunt' : 'aandachtspunten' }}</strong>
                <span class="font-normal"> — {{ $problems->implode(' • ') }}</span>
            </div>
        </div>
    @else
        <div class="flex items-center gap-3 rounded-xl px-4 py-3 mb-6 bg-green-50 text-green-800">
            <flux:icon name="check-circle" class="size-5 shrink-0" />
            <strong>Alles in orde</strong>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Server card --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-4">Server</flux:heading>

            <div class="divide-y divide-[var(--color-border-light)]">
                @if($memory)
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-sm text-[var(--color-text-secondary)]">Geheugen</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ ServerHealth::formatBytes($memory['used']) }} / {{ ServerHealth::formatBytes($memory['total']) }}</span>
                            <div class="w-20 h-1.5 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ match($memStatus) { 'red' => 'bg-red-500', 'amber' => 'bg-amber-500', default => 'bg-green-500' } }}" style="width: {{ $memory['percent'] }}%"></div>
                            </div>
                            <flux:badge size="sm" :color="$badgeColor($memStatus)" inset="top bottom">{{ $badgeLabel($memStatus) }}</flux:badge>
                        </div>
                    </div>
                    @if($memStatus !== 'green')
                        <div class="border-l-3 border-[var(--color-primary)] bg-[var(--color-primary)]/5 rounded-r-lg px-3 py-2.5 -mx-px">
                            @if($memStatus === 'red')
                                <p class="text-sm text-[var(--color-text-secondary)]">Het geheugen is bijna vol. De server kan vastlopen of processen geforceerd beëindigen.</p>
                                <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Controleer in Forge → Server → Processen welk proces het meeste geheugen gebruikt. Overweeg de server te herstarten als het probleem aanhoudt.</p>
                            @else
                                <p class="text-sm text-[var(--color-text-secondary)]">Het geheugen raakt vol. De server kan trager worden of processen beëindigen.</p>
                                <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Controleer in Forge → Server → Processen of er een proces ongewoon veel geheugen gebruikt.</p>
                            @endif
                        </div>
                    @endif
                @endif

                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Schijf</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ ServerHealth::formatBytes($disk['used']) }} / {{ ServerHealth::formatBytes($disk['total']) }}</span>
                        <div class="w-20 h-1.5 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ match($diskStatus) { 'red' => 'bg-red-500', 'amber' => 'bg-amber-500', default => 'bg-green-500' } }}" style="width: {{ $disk['percent'] }}%"></div>
                        </div>
                        <flux:badge size="sm" :color="$badgeColor($diskStatus)" inset="top bottom">{{ $badgeLabel($diskStatus) }}</flux:badge>
                    </div>
                </div>
                @if($diskStatus !== 'green')
                    <div class="border-l-3 border-[var(--color-primary)] bg-[var(--color-primary)]/5 rounded-r-lg px-3 py-2.5 -mx-px">
                        @if($diskStatus === 'red')
                            <p class="text-sm text-[var(--color-text-secondary)]">De schijf is bijna vol. De applicatie kan geen bestanden meer opslaan of loggen.</p>
                            <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Ruim onmiddellijk grote logbestanden of tijdelijke bestanden op via Forge → Server → Terminal.</p>
                        @else
                            <p class="text-sm text-[var(--color-text-secondary)]">De schijf raakt vol. Controleer of er grote logbestanden of uploads opgeruimd kunnen worden.</p>
                            <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Controleer de grootte van storage/logs en storage/app/public in Forge → Server → Bestanden.</p>
                        @endif
                    </div>
                @endif

                @if($load)
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-sm text-[var(--color-text-secondary)]">Serverbelasting</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ ServerHealth::loadLabel($load['1m']) }}</span>
                            <flux:badge size="sm" :color="$badgeColor($loadStatus)" inset="top bottom">{{ $badgeLabel($loadStatus) }}</flux:badge>
                        </div>
                    </div>
                    @if($loadStatus !== 'green')
                        <div class="border-l-3 border-[var(--color-primary)] bg-[var(--color-primary)]/5 rounded-r-lg px-3 py-2.5 -mx-px">
                            @if($loadStatus === 'red')
                                <p class="text-sm text-[var(--color-text-secondary)]">De server verwerkt meer taken dan hij aankan. Mogelijke oorzaken:</p>
                                <ul class="text-sm text-[var(--color-text-secondary)] list-disc ml-5 mt-1">
                                    <li>Een zware taak blokkeert de processor (bijv. PDF-verwerking)</li>
                                    <li>Te veel gelijktijdige achtergrondtaken</li>
                                </ul>
                                <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Controleer in Forge → Server → Processen welk proces de meeste CPU gebruikt.</p>
                            @else
                                <p class="text-sm text-[var(--color-text-secondary)]">De server is druk. Pagina's laden mogelijk trager dan normaal.</p>
                                <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Dit gaat meestal vanzelf over. Als het aanhoudt, controleer in Forge → Server → Processen of er een zware taak loopt.</p>
                            @endif
                        </div>
                    @endif
                @endif

                @if($uptime)
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-sm text-[var(--color-text-secondary)]">Uptime</span>
                        <span class="text-sm font-medium">{{ $uptime }}</span>
                    </div>
                @endif

                <details class="py-2.5">
                    <summary class="text-sm text-[var(--color-text-tertiary)] cursor-pointer hover:text-[var(--color-text-secondary)]">Technische details</summary>
                    <div class="mt-2 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-[var(--color-text-secondary)]">PHP / Laravel</span>
                            <span class="text-sm text-[var(--color-text-tertiary)]">{{ $phpVersion }} / {{ $laravelVersion }}</span>
                        </div>
                        @if($load)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-[var(--color-text-secondary)]">Serverbelasting (raw)</span>
                                <span class="text-sm text-[var(--color-text-tertiary)]">{{ $load['1m'] }} / {{ $load['5m'] }} / {{ $load['15m'] }}</span>
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        </flux:card>

        {{-- Queue card --}}
        <flux:card>
            <flux:heading size="lg" class="font-heading font-bold mb-4">Wachtrij</flux:heading>

            <div class="divide-y divide-[var(--color-border-light)]">
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Achtergrondtaken</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ $heartbeatOk ? $heartbeatLabel : 'Gestopt' }}</span>
                        <flux:badge size="sm" :color="$heartbeatOk ? 'green' : 'red'" inset="top bottom">{{ $heartbeatOk ? 'OK' : 'Kritiek' }}</flux:badge>
                    </div>
                </div>
                @if(! $heartbeatOk)
                    <div class="border-l-3 border-[var(--color-primary)] bg-[var(--color-primary)]/5 rounded-r-lg px-3 py-2.5 -mx-px">
                        <p class="text-sm text-[var(--color-text-secondary)]">De achtergrondverwerker reageert niet meer. Uploads en e-mails worden niet verwerkt.</p>
                        <p class="text-sm mt-1"><strong class="text-[var(--color-primary)]">Wat te doen:</strong> Ga naar Forge → jouw site → Queue en herstart de worker.</p>
                    </div>
                @endif

                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-[var(--color-text-secondary)]">Wachtend</span>
                    <span class="text-sm font-medium">{{ number_format($queue['pending']) }} jobs</span>
                </div>

                <div class="flex items-center justify-between py-2.5">
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
            <div class="divide-y divide-[var(--color-border-light)]">
                @foreach($errors as $error)
                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-3 py-2 text-sm">
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[var(--color-text-tertiary)] tabular-nums">{{ $error['relative_time'] }}</span>
                            @if($error['count'] > 1)
                                <span class="text-xs font-semibold text-red-700 bg-red-50 px-1.5 py-0.5 rounded-full">x{{ $error['count'] }}</span>
                            @endif
                        </div>
                        <span class="text-[var(--color-text-secondary)] truncate">{{ $error['message'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Timestamp --}}
    <p class="text-xs text-[var(--color-text-tertiary)] text-right mt-4">Bijgewerkt: {{ now()->format('j M Y \o\m H:i') }}</p>

</x-sidebar-layout>
