<?php

namespace App\Metrics;

use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;

class OnboardingInteraction30dRateMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $cohortStart = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subDays(30),
        };

        $cohort = User::query()
            ->whereNotNull('email_verified_at')
            ->where('role', '!=', 'admin')
            ->when($cohortStart !== null, fn ($q) => $q->where('created_at', '>=', $cohortStart))
            ->get(['id', 'email_verified_at']);

        $cohortCount = $cohort->count();

        if ($cohortCount === 0) {
            return new MetricValue(current: 0, unit: '%');
        }

        $userIds = $cohort->pluck('id');
        $usersById = $cohort->keyBy('id');

        $usersWithKudos = Like::query()
            ->whereIn('user_id', $userIds)
            ->where('type', 'kudos')
            ->get(['user_id', 'created_at'])
            ->filter(function ($like) use ($usersById) {
                $user = $usersById->get($like->user_id);

                if (! $user) {
                    return false;
                }

                // diffInDays returns 0 or negative for dates >= verified_at
                // so -30 <= diff <= 0 means within 30 days after verification
                $diff = $like->created_at->diffInDays($user->email_verified_at);

                return $diff >= -30 && $diff <= 0;
            })
            ->pluck('user_id')
            ->unique();

        $usersWithComment = Comment::query()
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'created_at'])
            ->filter(function ($comment) use ($usersById) {
                $user = $usersById->get($comment->user_id);

                if (! $user) {
                    return false;
                }

                // diffInDays returns 0 or negative for dates >= verified_at
                // so -30 <= diff <= 0 means within 30 days after verification
                $diff = $comment->created_at->diffInDays($user->email_verified_at);

                return $diff >= -30 && $diff <= 0;
            })
            ->pluck('user_id')
            ->unique();

        $interactorCount = $usersWithKudos->merge($usersWithComment)->unique()->count();
        $rate = (int) round($interactorCount / $cohortCount * 100);

        return new MetricValue(
            current: $rate,
            unit: '%',
            lowData: $cohortCount > 0 && $cohortCount < 5,
        );
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        $cohort = User::query()
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '<=', $date)
            ->where('role', '!=', 'admin')
            ->where('created_at', '>=', $date->subDays(29)->startOfDay())
            ->where('created_at', '<=', $date)
            ->get(['id', 'email_verified_at']);

        $cohortCount = $cohort->count();

        if ($cohortCount === 0) {
            return new MetricValue(current: 0, unit: '%');
        }

        $userIds = $cohort->pluck('id');
        $usersById = $cohort->keyBy('id');

        $usersWithKudos = Like::query()
            ->whereIn('user_id', $userIds)
            ->where('type', 'kudos')
            ->where('created_at', '<=', $date)
            ->get(['user_id', 'created_at'])
            ->filter(function ($like) use ($usersById) {
                $user = $usersById->get($like->user_id);

                if (! $user) {
                    return false;
                }

                $diff = $like->created_at->diffInDays($user->email_verified_at);

                return $diff >= -30 && $diff <= 0;
            })
            ->pluck('user_id')
            ->unique();

        $usersWithComment = Comment::query()
            ->whereIn('user_id', $userIds)
            ->where('created_at', '<=', $date)
            ->get(['user_id', 'created_at'])
            ->filter(function ($comment) use ($usersById) {
                $user = $usersById->get($comment->user_id);

                if (! $user) {
                    return false;
                }

                $diff = $comment->created_at->diffInDays($user->email_verified_at);

                return $diff >= -30 && $diff <= 0;
            })
            ->pluck('user_id')
            ->unique();

        $interactorCount = $usersWithKudos->merge($usersWithComment)->unique()->count();
        $rate = (int) round($interactorCount / $cohortCount * 100);

        return new MetricValue(
            current: $rate,
            unit: '%',
            lowData: $cohortCount > 0 && $cohortCount < 5,
        );
    }
}
