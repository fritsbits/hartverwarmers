<?php

namespace App\Metrics;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;

class OnboardingFollowupResponseRateMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $followUpLogs = OnboardingEmailLog::query()
            ->where('mail_key', 'like', 'download_followup_%')
            ->get(['user_id', 'mail_key', 'sent_at']);

        $sentCount = $followUpLogs->count();

        if ($sentCount === 0) {
            return new MetricValue(current: null, unit: '%');
        }

        $pairs = $followUpLogs->map(fn ($log) => [
            'user_id' => $log->user_id,
            'fiche_id' => (int) str_replace('download_followup_', '', $log->mail_key),
            'sent_at' => $log->sent_at,
        ]);

        $pairUserIds = $pairs->pluck('user_id')->unique()->values()->all();
        $pairFicheIds = $pairs->pluck('fiche_id')->unique()->values()->all();

        // Bulk-fetch all relevant kudos and comments in two queries instead of 2×N.
        $kudos = Like::query()
            ->whereIn('user_id', $pairUserIds)
            ->where('likeable_type', Fiche::class)
            ->whereIn('likeable_id', $pairFicheIds)
            ->where('type', 'kudos')
            ->get(['user_id', 'likeable_id', 'created_at']);

        $commentsLog = Comment::query()
            ->whereIn('user_id', $pairUserIds)
            ->where('commentable_type', Fiche::class)
            ->whereIn('commentable_id', $pairFicheIds)
            ->get(['user_id', 'commentable_id', 'created_at']);

        $kudosByUserFiche = [];
        foreach ($kudos as $k) {
            $kudosByUserFiche[$k->user_id][$k->likeable_id][] = $k->created_at;
        }

        $commentsByUserFiche = [];
        foreach ($commentsLog as $c) {
            $commentsByUserFiche[$c->user_id][$c->commentable_id][] = $c->created_at;
        }

        $responded = 0;

        foreach ($pairs as $pair) {
            $userId = $pair['user_id'];
            $ficheId = $pair['fiche_id'];
            $sentAt = $pair['sent_at'];

            $hasResponse = false;

            foreach ($kudosByUserFiche[$userId][$ficheId] ?? [] as $ts) {
                if ($ts >= $sentAt) {
                    $hasResponse = true;
                    break;
                }
            }

            if (! $hasResponse) {
                foreach ($commentsByUserFiche[$userId][$ficheId] ?? [] as $ts) {
                    if ($ts >= $sentAt) {
                        $hasResponse = true;
                        break;
                    }
                }
            }

            if ($hasResponse) {
                $responded++;
            }
        }

        return new MetricValue(current: (int) round($responded / $sentCount * 100), unit: '%');
    }
}
