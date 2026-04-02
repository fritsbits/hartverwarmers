<?php

namespace App\Observers;

use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use Illuminate\Database\UniqueConstraintViolationException;

class LikeObserver
{
    private const BOOKMARK_MILESTONES = [
        1 => 'mail_4',
        10 => 'mail_5',
        50 => 'mail_6',
    ];

    public function created(Like $like): void
    {
        if ($like->type !== 'bookmark' || $like->likeable_type !== Fiche::class) {
            return;
        }

        $fiche = $like->likeable;
        $owner = $fiche->user;

        if (! $owner || ! $owner->notify_on_onboarding_emails) {
            return;
        }

        $totalBookmarks = Like::bookmarks()
            ->whereHasMorph('likeable', Fiche::class, fn ($q) => $q->where('user_id', $owner->id))
            ->count();

        if (! isset(self::BOOKMARK_MILESTONES[$totalBookmarks])) {
            return;
        }

        $mailKey = self::BOOKMARK_MILESTONES[$totalBookmarks];

        if (OnboardingEmailLog::where('user_id', $owner->id)->where('mail_key', $mailKey)->exists()) {
            return;
        }

        $notification = match ($mailKey) {
            'mail_4' => new OnboardingFirstBookmarkNotification($fiche),
            'mail_5' => new OnboardingMilestone10BookmarksNotification($totalBookmarks),
            'mail_6' => new OnboardingMilestone50BookmarksNotification($totalBookmarks),
        };

        try {
            OnboardingEmailLog::create(['user_id' => $owner->id, 'mail_key' => $mailKey, 'sent_at' => now()]);
            $owner->notify($notification);
        } catch (UniqueConstraintViolationException) {
            // Concurrent bookmark — another request already logged this milestone
        }
    }
}
