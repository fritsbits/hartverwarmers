<?php

namespace App\Metrics;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\UserInteraction;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use BadMethodCallException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ThankRateMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        if ($range === 'alltime') {
            $current = $this->thankedRows(null);
            $previous = collect();
        } else {
            [$currentStart, $previousStart] = match ($range) {
                'week' => [now()->subDays(6)->startOfDay(), now()->subDays(13)->startOfDay()],
                'quarter' => [now()->subWeeks(12)->startOfWeek(), now()->subWeeks(24)->startOfWeek()],
                default => [now()->subDays(29)->startOfDay(), now()->subDays(59)->startOfDay()],
            };

            $all = $this->thankedRows($previousStart);
            $current = $all->filter(fn ($row) => $row['downloaded_at'] >= $currentStart);
            $previous = $all->filter(fn ($row) => $row['downloaded_at'] < $currentStart);
        }

        $currentDownloads = $current->count();
        $currentThanked = $current->filter(fn ($row) => $row['is_thanked'])->count();
        $currentRate = $currentDownloads > 0
            ? (int) round($currentThanked / $currentDownloads * 100)
            : 0;

        $previousDownloads = $previous->count();
        $previousThanked = $previous->filter(fn ($row) => $row['is_thanked'])->count();
        $previousRate = $previousDownloads > 0
            ? (int) round($previousThanked / $previousDownloads * 100)
            : null;

        return new MetricValue(
            current: $currentRate,
            previous: $previousRate,
            unit: '%',
            lowData: $currentDownloads > 0 && $currentDownloads < 5,
        );
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        throw new BadMethodCallException(static::class.'::computeAsOf not yet implemented.');
    }

    /**
     * @return Collection<int, array{user_id:int, fiche_id:int, downloaded_at: Carbon, is_thanked: bool}>
     */
    private function thankedRows(?Carbon $since): Collection
    {
        $downloads = UserInteraction::query()
            ->where('interactable_type', Fiche::class)
            ->where('type', 'download')
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->get(['user_id', 'interactable_id', 'created_at']);

        if ($downloads->isEmpty()) {
            return collect();
        }

        $userIds = $downloads->pluck('user_id')->unique()->values()->all();
        $ficheIds = $downloads->pluck('interactable_id')->unique()->values()->all();

        $kudosByPair = Like::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('likeable_id', $ficheIds)
            ->where('likeable_type', Fiche::class)
            ->where('type', 'kudos')
            ->where('count', '>', 0)
            ->get(['user_id', 'likeable_id', 'created_at'])
            ->groupBy(fn ($row) => $row->user_id.':'.$row->likeable_id)
            ->map(fn ($rows) => $rows->min('created_at'));

        $commentByPair = Comment::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('commentable_id', $ficheIds)
            ->where('commentable_type', Fiche::class)
            ->get(['user_id', 'commentable_id', 'created_at'])
            ->groupBy(fn ($row) => $row->user_id.':'.$row->commentable_id)
            ->map(fn ($rows) => $rows->min('created_at'));

        return $downloads->map(function ($download) use ($kudosByPair, $commentByPair) {
            $key = $download->user_id.':'.$download->interactable_id;
            $kudosAt = $kudosByPair->get($key);
            $commentAt = $commentByPair->get($key);

            $thankedViaKudos = $kudosAt !== null && $kudosAt >= $download->created_at;
            $thankedViaComment = $commentAt !== null && $commentAt >= $download->created_at;

            return [
                'user_id' => $download->user_id,
                'fiche_id' => $download->interactable_id,
                'downloaded_at' => $download->created_at,
                'is_thanked' => $thankedViaKudos || $thankedViaComment,
            ];
        });
    }
}
