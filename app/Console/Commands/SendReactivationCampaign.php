<?php

namespace App\Console\Commands;

use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\ReactivationNotification;
use App\Support\Reactivation\ReactivationContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendReactivationCampaign extends Command
{
    protected $signature = 'newsletter:send-reactivation {--dry-run} {--to= : Send one real test email to this registered address; no cohort or logs touched}';

    protected $description = 'Send the one-off dormant-contact reactivation campaign in a daily ramped batch.';

    public function handle(): int
    {
        if ($email = $this->option('to')) {
            return $this->sendTest($email);
        }

        if (! config('newsletter.reactivation_active')) {
            $this->info('Reactivation campaign is not active — skipping.');

            return self::SUCCESS;
        }

        $mailKey = config('newsletter.reactivation_mail_key');
        $batchSize = $this->todaysBatchSize($mailKey);
        $alreadyToday = $this->sentTodayCount($mailKey);
        $remaining = max(0, $batchSize - $alreadyToday);

        if ($remaining === 0) {
            $this->info("Daily batch of {$batchSize} already sent today.");

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $content = $dryRun ? null : ReactivationContent::build();
        $sent = 0;

        User::reactivationCohort()
            ->limit($remaining)
            ->get()
            ->each(function (User $user) use (&$sent, $content, $mailKey, $dryRun): void {
                if ($user->hasRecentNonExemptMail()) {
                    return;
                }

                if ($dryRun) {
                    $this->line("[dry-run] would send to {$user->email}");
                    $sent++;

                    return;
                }

                $user->notify(new ReactivationNotification($content));

                OnboardingEmailLog::create([
                    'user_id' => $user->id,
                    'mail_key' => $mailKey,
                    'sent_at' => now(),
                ]);

                $sent++;
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Reactivation: {$sent} of {$batchSize} this run.");

        return self::SUCCESS;
    }

    private function sendTest(string $email): int
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No registered user with email {$email} — a test email needs an existing account.");

            return self::FAILURE;
        }

        $user->notify(new ReactivationNotification(ReactivationContent::build()));
        $this->info("Test reactivation email sent to {$email} (no cohort user touched, nothing logged).");

        return self::SUCCESS;
    }

    private function todaysBatchSize(string $mailKey): int
    {
        $ramp = config('newsletter.reactivation_ramp');

        $priorDays = OnboardingEmailLog::where('mail_key', $mailKey)
            ->whereDate('sent_at', '<', now()->toDateString())
            ->distinct()
            ->count(DB::raw('DATE(sent_at)'));

        return $ramp[$priorDays] ?? end($ramp);
    }

    private function sentTodayCount(string $mailKey): int
    {
        return OnboardingEmailLog::where('mail_key', $mailKey)
            ->whereDate('sent_at', now()->toDateString())
            ->count();
    }
}
