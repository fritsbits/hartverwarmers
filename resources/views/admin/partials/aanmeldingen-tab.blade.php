@php
    $maxCount = collect($signupTrend)->max('count') ?: 1;
    $firstLabel = $signupTrend[0]['label'] ?? null;
    $lastLabel = collect($signupTrend)->last()['label'] ?? null;
@endphp

{{-- Signup trend --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Aanmeldingen</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
        Bezoekers maken een account · {{ $signupStats['rangeLabel'] }}
    </p>

    @if(empty($signupTrend))
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen aanmeldingen in deze periode.</p>
    @else
        {{-- Sparkline --}}
        <div class="flex items-end gap-1.5 h-16 mb-1">
            @foreach($signupTrend as $bucket)
                @if($bucket['count'] > 0)
                    <div
                        class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                        style="height: {{ max(4, (int) round($bucket['count'] / $maxCount * 100)) }}%"
                        title="{{ $bucket['label'] }}: {{ $bucket['count'] }}"
                    ></div>
                @else
                    <div class="flex-1 rounded-t bg-[var(--color-border-light)] opacity-40" style="height: 2px"
                         title="{{ $bucket['label'] }}: 0"></div>
                @endif
            @endforeach
        </div>
        @if($firstLabel && $lastLabel && $firstLabel !== $lastLabel)
            <div class="flex justify-between text-xs text-[var(--color-text-secondary)] mb-4">
                <span>{{ $firstLabel }}</span>
                <span>{{ $lastLabel }}</span>
            </div>
        @else
            <div class="mb-4"></div>
        @endif
    @endif

    {{-- Stats row --}}
    <div class="flex gap-6">
        <div>
            <div class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $signupStats['currentCount'] }}</div>
            <div class="text-xs text-[var(--color-text-secondary)]">
                {{ $signupStats['rangeLabel'] }}
                @if($signupStats['delta'] !== null)
                    <span class="font-semibold {{ $signupStats['delta'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        &nbsp;{{ $signupStats['delta'] >= 0 ? '+' : '' }}{{ $signupStats['delta'] }}
                    </span>
                @endif
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold tabular-nums">{{ $signupStats['totalMembers'] }}</div>
            <div class="text-xs text-[var(--color-text-secondary)]">totaal leden</div>
        </div>
    </div>
</flux:card>

{{-- Verification rate --}}
<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">E-mailverificatie</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">
        Welk percentage van nieuwe accounts bevestigt hun adres?
    </p>

    @if($signupStats['cohortCount'] === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen aanmeldingen om te verifiëren.</p>
    @else
        <div class="flex items-center gap-4 mb-2">
            <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden"
                 role="progressbar"
                 aria-valuenow="{{ $signupStats['verificationRate'] }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-label="E-mailverificatiegraad">
                <div class="h-full bg-[var(--color-primary)] rounded-full"
                     style="width: {{ $signupStats['verificationRate'] }}%"></div>
            </div>
            <span class="text-2xl font-bold tabular-nums text-[var(--color-primary)] shrink-0">
                {{ $signupStats['verificationRate'] }}%
            </span>
        </div>
        <p class="text-xs text-[var(--color-text-secondary)]">
            {{ $signupStats['verifiedCount'] }} van {{ $signupStats['cohortCount'] }} nieuwe gebruikers verifieerden hun e-mail
        </p>
        @if($signupStats['verificationLowData'])
            <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
        @endif
    @endif
</flux:card>
