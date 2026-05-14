<?php

namespace App\Console\Commands;

use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\MonthlyDigestNotification;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Console\Command;

class SendMonthlyCohortNewsletter extends Command
{
    protected $signature = 'newsletter:send-monthly-cohort {--for=}';

    protected $description = 'Send the monthly digest to users at a 30-day signup anniversary today';

    public function handle(Composer $composer): int
    {
        if ($email = $this->option('for')) {
            return $this->sendToSingleAddress($email, $composer);
        }

        $payload = $composer->compose(now());

        if ($payload->isEmpty()) {
            $this->info('No themes and no recent fiches — skipping all sends for today.');

            return Command::SUCCESS;
        }

        $sent = 0;

        User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('newsletter_unsubscribed_at')
            ->chunkById(200, function ($users) use ($payload, &$sent): void {
                foreach ($users as $user) {
                    if (! $user->qualifiesForMonthlyDigestToday()) {
                        continue;
                    }

                    if ($user->hasRecentNonExemptMail()) {
                        continue;
                    }

                    $cycle = $user->currentDigestCycleNumber();

                    $user->notify(new MonthlyDigestNotification($payload, cycle: $cycle));

                    OnboardingEmailLog::create([
                        'user_id' => $user->id,
                        'mail_key' => "newsletter-cycle-{$cycle}",
                        'sent_at' => now(),
                    ]);

                    $sent++;
                }
            });

        $this->info("Sent: {$sent}.");

        return Command::SUCCESS;
    }

    private function sendToSingleAddress(string $email, Composer $composer): int
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user with email {$email}.");

            return Command::FAILURE;
        }

        $payload = $composer->compose(now());

        if ($payload->isEmpty()) {
            $this->warn('Payload is empty (no themes, no recent fiches) — sending anyway for QA.');
        }

        $user->notify(new MonthlyDigestNotification($payload, cycle: $user->currentDigestCycleNumber()));

        $this->info("Sent digest to {$email}.");

        return Command::SUCCESS;
    }
}
