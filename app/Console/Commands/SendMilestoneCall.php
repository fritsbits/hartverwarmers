<?php

namespace App\Console\Commands;

use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\MilestoneCallNotification;
use App\Support\MilestoneCall\MilestoneCallContent;
use Illuminate\Console\Command;

class SendMilestoneCall extends Command
{
    protected $signature = 'newsletter:send-milestone-call {--dry-run} {--to= : Send one real test email to this registered address; nothing logged}';

    protected $description = 'Ask every contributor to help reach the fiche milestone, until it is reached.';

    public function handle(): int
    {
        if ($email = $this->option('to')) {
            return $this->sendTest($email);
        }

        if (! config('newsletter.milestone_call_active')) {
            $this->info('Milestone call is not active — skipping.');

            return self::SUCCESS;
        }

        $content = MilestoneCallContent::build();

        if ($content->reached()) {
            $this->info("Milestone of {$content->target} reached — nothing left to ask.");

            return self::SUCCESS;
        }

        $mailKey = config('newsletter.milestone_call_mail_key');
        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        // No ramp: the cohort is a few hundred people at most. Whoever the
        // 24h cap holds back today is picked up by tomorrow's run.
        User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('newsletter_unsubscribed_at')
            ->whereHas('fiches', fn ($q) => $q->published())
            ->whereDoesntHave('onboardingEmailLogs', fn ($q) => $q->where('mail_key', $mailKey))
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->chunkById(200, function ($users) use ($content, $mailKey, $dryRun, &$sent): void {
                foreach ($users as $user) {
                    if ($user->hasRecentNonExemptMail()) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("[dry-run] would send to {$user->email}");
                        $sent++;

                        continue;
                    }

                    $user->notify(new MilestoneCallNotification($content, ownFichesCount: $user->fiches_count));

                    OnboardingEmailLog::create([
                        'user_id' => $user->id,
                        'mail_key' => $mailKey,
                        'sent_at' => now(),
                    ]);

                    $sent++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Milestone call: {$sent} sent.");

        return self::SUCCESS;
    }

    private function sendTest(string $email): int
    {
        $user = User::where('email', $email)->withCount(['fiches' => fn ($q) => $q->published()])->first();

        if (! $user) {
            $this->error("No registered user with email {$email} — a test email needs an existing account.");

            return self::FAILURE;
        }

        $user->notify(new MilestoneCallNotification(MilestoneCallContent::build(), ownFichesCount: $user->fiches_count));
        $this->info("Test milestone call sent to {$email} (nothing logged).");

        return self::SUCCESS;
    }
}
