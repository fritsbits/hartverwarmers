<?php

namespace App\Console\Commands;

use App\Mail\FicheCommentDigestMail;
use App\Models\Fiche;
use App\Models\PendingNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCommentDigests extends Command
{
    protected $signature = 'notifications:send-digests {--frequency=daily}';

    protected $description = 'Send comment digest emails based on user notification frequency.';

    public function handle(): int
    {
        $frequency = $this->option('frequency');

        if (! in_array($frequency, ['daily', 'weekly'], true)) {
            $this->error("Invalid frequency: {$frequency}. Use 'daily' or 'weekly'.");

            return self::INVALID;
        }

        User::query()
            ->where('notification_frequency', $frequency)
            ->whereHas('pendingNotifications', fn ($q) => $q->where('type', 'fiche_comment'))
            ->with(['pendingNotifications' => fn ($q) => $q->where('type', 'fiche_comment')])
            ->lazyById(100)
            ->each(fn (User $user) => $this->sendDigestsForUser($user));

        return self::SUCCESS;
    }

    private function sendDigestsForUser(User $user): void
    {
        $byFiche = $user->pendingNotifications->groupBy('fiche_id');

        foreach ($byFiche as $ficheId => $notifications) {
            $fiche = Fiche::with('initiative')->find($ficheId);
            $ids = $notifications->pluck('id');

            if ($fiche === null) {
                PendingNotification::whereIn('id', $ids)->delete();

                continue;
            }

            try {
                Mail::to($user)->send(new FicheCommentDigestMail(
                    $user,
                    $fiche,
                    $notifications->pluck('payload')->all(),
                ));
            } catch (\Throwable $e) {
                report($e);
                $this->error("Failed to send digest to user {$user->id} for fiche {$ficheId}: {$e->getMessage()}");

                continue;
            }

            PendingNotification::whereIn('id', $ids)->delete();
        }
    }
}
