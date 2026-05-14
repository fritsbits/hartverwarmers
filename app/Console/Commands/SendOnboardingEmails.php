<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
use App\Notifications\OnboardingTopFiveNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendOnboardingEmails extends Command
{
    protected $signature = 'onboarding:send-emails';

    protected $description = 'Send behaviour-triggered onboarding emails to new users';

    public function handle(): int
    {
        $this->sendMail1();
        $this->sendMail2();
        $this->sendMail3();

        $this->info('Onboarding emails processed.');

        return Command::SUCCESS;
    }

    private function eligibleUsers(string $mailKey, int $daysAfterActivation): Builder
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '<=', now()->subDays($daysAfterActivation))
            ->where('email_verified_at', '>=', now()->subDays($daysAfterActivation + 60))
            ->where('notify_on_onboarding_emails', true)
            ->whereDoesntHave('onboardingEmailLogs', fn ($q) => $q->where('mail_key', $mailKey));
    }

    private function log(User $user, string $mailKey): void
    {
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => $mailKey, 'sent_at' => now()]);
    }

    private function sendMail1(): void
    {
        $this->eligibleUsers('mail_1', 3)->chunk(100, function ($users): void {
            foreach ($users as $user) {
                if ($user->hasRecentNonExemptMail()) {
                    continue;
                }
                $user->notify(new OnboardingCuratedActivitiesNotification);
                $this->log($user, 'mail_1');
            }
        });
    }

    private function sendMail2(): void
    {
        $this->eligibleUsers('mail_2', 7)
            ->whereHas('onboardingEmailLogs', fn ($q) => $q
                ->where('mail_key', 'mail_1')
                ->where('sent_at', '<=', now()->subDay())
            )
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    if ($user->hasRecentNonExemptMail()) {
                        continue;
                    }
                    $user->notify(new OnboardingTopFiveNotification);
                    $this->log($user, 'mail_2');
                }
            });
    }

    private function sendMail3(): void
    {
        User::query()
            ->whereNotNull('email_verified_at')
            ->where('notify_on_onboarding_emails', true)
            ->whereDoesntHave('onboardingEmailLogs', fn ($q) => $q->where('mail_key', 'mail_3'))
            ->whereHas('interactions', fn ($q) => $q
                ->where('type', 'download')
                ->where('interactable_type', Fiche::class), '>=', 5
            )
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    if ($user->hasRecentNonExemptMail()) {
                        continue;
                    }

                    $downloadCount = $user->interactions()
                        ->where('type', 'download')
                        ->where('interactable_type', Fiche::class)
                        ->count();

                    if ($user->fiches()->published()->doesntExist()) {
                        $user->notify(new OnboardingDownloadMilestoneNotification($downloadCount));
                    }

                    $this->log($user, 'mail_3');
                }
            });
    }
}
