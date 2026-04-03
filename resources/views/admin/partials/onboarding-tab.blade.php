{{-- KR list --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Onboarding via e-mail</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">
        Nieuwe gebruikers activeren tot betrokken communityleden · laatste 30 dagen
        @if($onboardingStats['newUsersCount'] > 0)
            <span class="text-[var(--color-text-tertiary)]">· {{ $onboardingStats['newUsersCount'] }} gebruikers</span>
        @endif
    </p>

    <div class="flex flex-col gap-3">
        {{-- KR1 --}}
        <div class="p-3 bg-[var(--color-bg-cream)] rounded-lg">
            <div class="flex items-baseline justify-between mb-1">
                <span class="text-sm font-semibold">KR1 · Return visit binnen 7 dagen</span>
                @if($onboardingStats['newUsersCount'] > 0)
                    <span class="text-sm font-bold text-[var(--color-primary)] tabular-nums">
                        {{ $onboardingStats['kr1Percentage'] }}%
                        <span class="font-normal text-[var(--color-text-tertiary)]">/ 50%</span>
                    </span>
                @else
                    <span class="text-sm text-[var(--color-text-tertiary)]">—</span>
                @endif
            </div>
            <div class="h-1 bg-[var(--color-border-light)] rounded-full mb-1">
                <div class="h-1 bg-[var(--color-primary)] rounded-full"
                     style="width: {{ min(100, ($onboardingStats['kr1Percentage'] / 50) * 100) }}%"></div>
            </div>
            <p class="text-xs text-[var(--color-text-secondary)]">
                {{ $onboardingStats['kr1Count'] }} van {{ $onboardingStats['newUsersCount'] }} nieuwe gebruikers keerden terug
            </p>
        </div>

        {{-- KR2 --}}
        <div class="p-3 bg-[var(--color-bg-cream)] rounded-lg">
            <div class="flex items-baseline justify-between mb-1">
                <span class="text-sm font-semibold">KR2 · Interactie binnen 30 dagen</span>
                @if($onboardingStats['newUsersCount'] > 0)
                    <span class="text-sm font-bold text-[var(--color-primary)] tabular-nums">
                        {{ $onboardingStats['kr2Percentage'] }}%
                        <span class="font-normal text-[var(--color-text-tertiary)]">/ 50%</span>
                    </span>
                @else
                    <span class="text-sm text-[var(--color-text-tertiary)]">—</span>
                @endif
            </div>
            <div class="h-1 bg-[var(--color-border-light)] rounded-full mb-1">
                <div class="h-1 bg-[var(--color-primary)] rounded-full"
                     style="width: {{ min(100, ($onboardingStats['kr2Percentage'] / 50) * 100) }}%"></div>
            </div>
            <p class="text-xs text-[var(--color-text-secondary)]">
                {{ $onboardingStats['kr2Count'] }} van {{ $onboardingStats['newUsersCount'] }} gaven een kudos of reactie
            </p>
        </div>

        {{-- KR3 --}}
        <div class="p-3 bg-[var(--color-bg-cream)] rounded-lg {{ $onboardingStats['kr3SentCount'] === 0 ? 'opacity-50' : '' }}">
            <div class="flex items-baseline justify-between mb-1">
                <span class="text-sm font-semibold">KR3 · Follow-up reactie na download</span>
                @if($onboardingStats['kr3Percentage'] !== null)
                    <span class="text-sm font-bold text-[var(--color-primary)] tabular-nums">
                        {{ $onboardingStats['kr3Percentage'] }}%
                    </span>
                @else
                    <span class="text-sm text-[var(--color-text-tertiary)]">nog meten</span>
                @endif
            </div>
            <div class="h-1 bg-[var(--color-border-light)] rounded-full mb-1">
                @if($onboardingStats['kr3Percentage'] !== null)
                    <div class="h-1 bg-[var(--color-primary)] rounded-full"
                         style="width: {{ min(100, $onboardingStats['kr3Percentage']) }}%"></div>
                @endif
            </div>
            <p class="text-xs text-[var(--color-text-tertiary)]">
                @if($onboardingStats['kr3SentCount'] === 0)
                    target bepalen na eerste maand
                @else
                    {{ $onboardingStats['kr3RespondedCount'] }} van {{ $onboardingStats['kr3SentCount'] }} follow-up e-mails leidde tot reactie
                @endif
            </p>
        </div>
    </div>
</flux:card>

{{-- Sent emails --}}
<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">Verstuurde e-mails</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-4">Laatste 30 dagen</p>

    @php
        // mail_4/5/6 keys come from LikeObserver (first bookmark, 10 bookmarks, 50 bookmarks)
        $emailLabels = [
            'mail_1' => 'Gecureerde activiteiten',
            'mail_2' => 'Top 5 meest bewaard',
            'mail_3' => 'Download milestone',
            'download_followup' => 'Download follow-up',
            'mail_4' => 'Eerste bookmark op jouw fiche',
            'mail_5' => 'Mijlpaal 10 bookmarks',
            'mail_6' => 'Mijlpaal 50 bookmarks',
        ];
    @endphp

    <div class="divide-y divide-[var(--color-border-light)]">
        @foreach($emailLabels as $key => $label)
            <div class="flex items-center gap-3 py-2">
                <span class="flex-1 text-sm text-[var(--color-text-secondary)]">{{ $label }}</span>
                <span class="text-sm font-semibold tabular-nums {{ ($onboardingEmailCounts[$key] ?? 0) > 0 ? 'text-[var(--color-text-primary)]' : 'text-[var(--color-text-tertiary)]' }}">
                    {{ $onboardingEmailCounts[$key] ?? 0 }}
                </span>
            </div>
        @endforeach
    </div>
</flux:card>
