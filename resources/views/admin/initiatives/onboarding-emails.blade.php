@php
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
