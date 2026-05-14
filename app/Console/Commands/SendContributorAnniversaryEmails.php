<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\ContributorAnniversaryNotification;
use App\Services\ContributorAnniversary\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendContributorAnniversaryEmails extends Command
{
    protected $signature = 'contributors:send-anniversary-emails {--for=}';

    protected $description = 'Send yearly anniversary emails to contributors on their first-published-fiche anniversary';

    public function handle(Composer $composer): int
    {
        if ($email = $this->option('for')) {
            return $this->sendToSingleAddress($email, $composer);
        }

        $today = now();
        $sent = 0;

        User::query()
            ->whereNotNull('email_verified_at')
            ->where('notify_on_kudos_milestones', true)
            ->whereHas('fiches', fn ($q) => $q->where('published', true))
            ->chunkById(200, function ($users) use ($today, $composer, &$sent): void {
                foreach ($users as $user) {
                    if ($this->sendIfEligible($user, $today, $composer)) {
                        $sent++;
                    }
                }
            });

        $this->info("Sent: {$sent}.");

        return Command::SUCCESS;
    }

    private function sendIfEligible(User $user, Carbon $today, Composer $composer): bool
    {
        $anchor = $this->anchorFor($user);
        if (! $anchor) {
            return false;
        }

        if (! $this->todayMatchesAnchor($today, $anchor)) {
            return false;
        }

        $year = $today->year - $anchor->year;
        if ($year < 1) {
            return false;
        }

        $mailKey = "anniversary-year-{$year}";

        if (OnboardingEmailLog::where('user_id', $user->id)->where('mail_key', $mailKey)->exists()) {
            return false;
        }

        if ($user->hasRecentNonExemptMail()) {
            return false;
        }

        $payload = $composer->compose($user);
        $user->notify(new ContributorAnniversaryNotification($payload, year: $year));

        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => $mailKey,
            'sent_at' => now(),
        ]);

        return true;
    }

    private function anchorFor(User $user): ?Carbon
    {
        $value = Fiche::query()
            ->where('user_id', $user->id)
            ->where('published', true)
            ->orderBy('created_at')
            ->value('created_at');

        return $value ? Carbon::parse($value) : null;
    }

    private function todayMatchesAnchor(Carbon $today, Carbon $anchor): bool
    {
        if ($today->month === $anchor->month && $today->day === $anchor->day) {
            return true;
        }

        // Leap-year fallback: Feb-29 anchor fires on Feb 28 in non-leap years.
        return $anchor->month === 2
            && $anchor->day === 29
            && $today->month === 2
            && $today->day === 28
            && ! $today->isLeapYear();
    }

    private function sendToSingleAddress(string $email, Composer $composer): int
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user with email {$email}.");

            return Command::FAILURE;
        }

        $anchor = $this->anchorFor($user);
        if (! $anchor) {
            $this->error("User {$email} has no published fiches — nothing to anchor anniversary on.");

            return Command::FAILURE;
        }

        $year = max(1, now()->year - $anchor->year);
        $payload = $composer->compose($user);

        $user->notify(new ContributorAnniversaryNotification($payload, year: $year));

        $this->info("Sent anniversary email to {$email} (year {$year}).");

        return Command::SUCCESS;
    }
}
