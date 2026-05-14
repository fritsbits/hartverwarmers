<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\Okr\Objective;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    private ?Collection $allThankedDownloads = null;

    public function __invoke(): View
    {
        $tab = request()->get('tab', 'overzicht');
        if (! in_array($tab, ['overzicht', 'presentatiekwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief'], true)) {
            $tab = 'overzicht';
        }

        $range = request()->get('range', 'month');
        if (! in_array($range, ['week', 'month', 'quarter', 'alltime'], true)) {
            $range = 'month';
        }

        $cutoff = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subWeeks(4),
        };

        $rangeLabel = match ($range) {
            'week' => 'laatste week',
            'quarter' => 'laatste 3 maanden',
            'alltime' => 'sinds start',
            default => 'laatste maand',
        };

        if ($tab === 'presentatiekwaliteit') {
            $weeklyTrend = $this->trend($range);
            $trendDelta = $this->trendDelta($weeklyTrend);
            $lastFiches = $this->lastFiches();
            $scored = $lastFiches->filter(fn ($f) => $f->presentation_score !== null);
            $lastFiveAvg = $scored->isNotEmpty() ? (int) round($scored->avg('presentation_score')) : null;
            $globalAvg = $this->globalAvg();
            $fichesWithSuggestions = Fiche::query()
                ->published()
                ->whereNotNull('ai_suggestions')
                ->when($cutoff !== null, fn ($q) => $q->where('created_at', '>=', $cutoff))
                ->with('initiative:id,slug')
                ->get(['id', 'title', 'slug', 'initiative_id', 'ai_suggestions']);
            $ficheAdoption = $this->computeFicheAdoption($fichesWithSuggestions);
            $adoption = $ficheAdoption['adoption'];
            $fieldAdoption = $ficheAdoption['fieldAdoption'];
            $ficheAdoptionDetails = $ficheAdoption['details'];
            $lowestScoredFiches = $this->lowestScoredFiches();
            $recentAiSuggestionAcceptances = $this->recentAiSuggestionAcceptances();
        } else {
            $weeklyTrend = [];
            $trendDelta = null;
            $lastFiches = collect();
            $lastFiveAvg = null;
            $globalAvg = null;
            $adoption = ['withSuggestions' => 0, 'withAnyApplied' => 0, 'adoptionRate' => 0];
            $fieldAdoption = [];
            $ficheAdoptionDetails = [];
            $lowestScoredFiches = [];
            $recentAiSuggestionAcceptances = [];
        }

        $signupTrend = $tab === 'onboarding' ? $this->signupTrend($range) : [];
        $signupStats = $tab === 'onboarding' ? $this->signupStats($range) : [];

        $objectives = Objective::orderBy('position')->get();

        $currentObjective = null;
        if ($tab !== 'overzicht') {
            $currentObjective = $objectives->firstWhere('slug', $tab);
            if ($currentObjective) {
                $currentObjective->load(['keyResults', 'initiatives']);
            }
        }

        return view('admin.dashboard', [
            'tab' => $tab,
            'range' => $range,
            'objectives' => $objectives,
            'currentObjective' => $currentObjective,
            'rangeLabel' => $rangeLabel,
            'weeklyTrend' => $weeklyTrend,
            'trendDelta' => $trendDelta,
            'lastFiches' => $lastFiches,
            'lastFiveAvg' => $lastFiveAvg,
            'globalAvg' => $globalAvg,
            ...$adoption,
            'fieldAdoption' => $fieldAdoption,
            'ficheAdoptionDetails' => $ficheAdoptionDetails,
            'lowestScoredFiches' => $lowestScoredFiches,
            'recentAiSuggestionAcceptances' => $recentAiSuggestionAcceptances,
            'onboardingStats' => $tab === 'onboarding' ? $this->onboardingStats($range) : [],
            'onboardingEmailCounts' => $tab === 'onboarding' ? $this->onboardingEmailCounts($range) : [],
            'recentSignupsFunnel' => $tab === 'onboarding' ? $this->recentSignupsFunnel() : [],
            'stalledVerifications' => $tab === 'onboarding' ? $this->stalledVerifications() : [],
            'signupTrend' => $signupTrend,
            'signupStats' => $signupStats,
            'thankTrend' => $tab === 'bedankjes' ? $this->thankTrend($range) : [],
            'thankStats' => $tab === 'bedankjes' ? $this->thankStats($range) : [],
            'recentThankComments' => $tab === 'bedankjes' ? $this->recentThankComments() : [],
            'topThankedFiches' => $tab === 'bedankjes' ? $this->topThankedFiches($range, $cutoff) : [],
            'newsletterTrend' => $tab === 'nieuwsbrief' ? $this->newsletterTrend($range) : [],
            'newsletterStats' => $tab === 'nieuwsbrief' ? $this->newsletterStats($range) : [],
            'unsubscribeByCycle' => $tab === 'nieuwsbrief' ? $this->unsubscribeByCycle($range) : [],
            'activationStats' => $tab === 'nieuwsbrief' ? $this->activationStats($range) : [],
            'upcomingNewsletterSends' => $tab === 'nieuwsbrief' ? $this->upcomingNewsletterSends() : [],
            'lastNewsletterDigestMeta' => $tab === 'nieuwsbrief' ? $this->lastNewsletterDigestMeta() : null,
            'recentUnsubscribes' => $tab === 'nieuwsbrief' ? $this->recentUnsubscribes() : [],
        ]);
    }

    /** @return array<int, array{week_key: string|int, week_label: string, avg_score: int|null}> */
    private function trend(string $range): array
    {
        return match ($range) {
            'week' => $this->dailyTrend(),
            'quarter' => $this->quarterlyTrend(),
            'alltime' => $this->alltimeTrend(),
            default => $this->monthlyTrend(),
        };
    }

    /** @return array<int, array{week_key: string, week_label: string, avg_score: int|null}> */
    private function dailyTrend(): array
    {
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->where('quality_assessed_at', '>=', now()->subDays(7))
            ->get(['presentation_score', 'quality_assessed_at']);

        $grouped = [];
        foreach ($fiches as $fiche) {
            $key = $fiche->quality_assessed_at->format('Y-m-d');
            $grouped[$key][] = $fiche->presentation_score;
        }

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $label = $date->isoFormat('D MMM');

            if (isset($grouped[$key])) {
                $scores = $grouped[$key];
                $avg = (int) round(array_sum($scores) / count($scores));
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => $avg];
            } else {
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => null];
            }
        }

        return $result;
    }

    /** @return array<int, array{week_key: int, week_label: string, avg_score: int|null}> */
    private function monthlyTrend(): array
    {
        return $this->weeklyTrend(weeks: 4);
    }

    /** @return array<int, array{week_key: int, week_label: string, avg_score: int|null}> */
    private function quarterlyTrend(): array
    {
        return $this->weeklyTrend(weeks: 13);
    }

    /** @return array<int, array{week_key: int, week_label: string, avg_score: int|null}> */
    private function weeklyTrend(int $weeks): array
    {
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->where('quality_assessed_at', '>=', now()->subWeeks($weeks - 1)->startOfWeek())
            ->get(['presentation_score', 'quality_assessed_at']);

        $grouped = [];
        foreach ($fiches as $fiche) {
            $date = $fiche->quality_assessed_at;
            $key = (int) $date->format('oW');
            $grouped[$key][] = $fiche->presentation_score;
        }

        $result = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = (int) $date->format('oW');
            $label = $date->isoFormat('D MMM');

            if (isset($grouped[$key])) {
                $scores = $grouped[$key];
                $avg = (int) round(array_sum($scores) / count($scores));
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => $avg];
            } else {
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => null];
            }
        }

        return $result;
    }

    /** @return array<int, array{week_key: string, week_label: string, avg_score: int|null}> */
    private function alltimeTrend(): array
    {
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->get(['presentation_score', 'quality_assessed_at']);

        if ($fiches->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($fiches as $fiche) {
            $key = $fiche->quality_assessed_at->format('Y-m');
            $grouped[$key][] = $fiche->presentation_score;
        }

        $earliest = $fiches->min('quality_assessed_at')->copy()->startOfMonth();
        $end = now()->startOfMonth();

        $result = [];
        $cursor = $earliest->copy();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $label = $cursor->isoFormat('MMM YYYY');

            if (isset($grouped[$key])) {
                $scores = $grouped[$key];
                $avg = (int) round(array_sum($scores) / count($scores));
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => $avg];
            } else {
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => null];
            }
            $cursor->addMonth();
        }

        return $result;
    }

    private function lastFiches(): Collection
    {
        return Fiche::query()
            ->published()
            ->with('initiative:id,slug')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'presentation_score', 'created_at', 'initiative_id']);
    }

    private function globalAvg(): ?int
    {
        return Cache::remember(
            'admin:dashboard:global-avg',
            now()->addMinutes(5),
            function () {
                $avg = Fiche::query()
                    ->published()
                    ->whereNotNull('presentation_score')
                    ->avg('presentation_score');

                return $avg !== null ? (int) round($avg) : null;
            }
        );
    }

    /** @param array<int, array{avg_score: int|null}> $trend */
    private function trendDelta(array $trend): ?int
    {
        $scored = array_values(array_filter($trend, fn ($w) => $w['avg_score'] !== null));
        if (count($scored) < 2) {
            return null;
        }

        return $scored[array_key_last($scored)]['avg_score'] - $scored[0]['avg_score'];
    }

    /**
     * Single-pass aggregator producing adoption summary, per-field stats, and
     * per-fiche details from one walk over the fiches collection. Replaces the
     * three separate methods that each re-iterated the same set.
     *
     * @param  Collection<int, Fiche>  $fiches
     * @return array{
     *   adoption: array{withSuggestions: int, withAnyApplied: int, adoptionRate: int},
     *   fieldAdoption: array<string, array{suggested: int, applied: int, rate: int, label: string}>,
     *   details: array<int, array{title: string, url: string, fields: array<string, array{suggested: bool, applied: bool, label: string, shortLabel: string}>, adoptedCount: int, suggestedCount: int}>,
     * }
     */
    private function computeFicheAdoption(Collection $fiches): array
    {
        $fieldMeta = [
            'title' => ['label' => 'Titel', 'shortLabel' => 'Titel'],
            'description' => ['label' => 'Omschrijving', 'shortLabel' => 'Omschr.'],
            'preparation' => ['label' => 'Voorbereiding', 'shortLabel' => 'Voorb.'],
            'inventory' => ['label' => 'Benodigdheden', 'shortLabel' => 'Ben.'],
            'process' => ['label' => 'Werkwijze', 'shortLabel' => 'Werkw.'],
        ];

        $fieldStats = [];
        foreach ($fieldMeta as $key => $meta) {
            $fieldStats[$key] = ['suggested' => 0, 'applied' => 0, 'label' => $meta['label']];
        }

        $withSuggestions = 0;
        $withAnyApplied = 0;
        $details = [];

        foreach ($fiches as $fiche) {
            $suggestions = $fiche->ai_suggestions;
            $applied = $suggestions['applied'] ?? [];

            $perFiche = [];
            $suggestedCount = 0;
            $adoptedCount = 0;

            foreach ($fieldMeta as $key => $meta) {
                $suggested = isset($suggestions[$key]) && $suggestions[$key] !== '';
                $isApplied = in_array($key, $applied, true);

                if ($suggested) {
                    $suggestedCount++;
                    $fieldStats[$key]['suggested']++;
                }

                if ($isApplied) {
                    $fieldStats[$key]['applied']++;
                }

                if ($suggested && $isApplied) {
                    $adoptedCount++;
                }

                $perFiche[$key] = [
                    'suggested' => $suggested,
                    'applied' => $isApplied,
                    'label' => $meta['label'],
                    'shortLabel' => $meta['shortLabel'],
                ];
            }

            if ($suggestedCount === 0) {
                continue;
            }

            $withSuggestions++;
            if (! empty($applied)) {
                $withAnyApplied++;
            }

            $details[] = [
                'title' => $fiche->title,
                'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                'fields' => $perFiche,
                'adoptedCount' => $adoptedCount,
                'suggestedCount' => $suggestedCount,
            ];
        }

        foreach ($fieldStats as $key => $stats) {
            $fieldStats[$key]['rate'] = $stats['suggested'] > 0
                ? (int) round($stats['applied'] / $stats['suggested'] * 100)
                : 0;
        }

        $adoptionRate = $withSuggestions > 0
            ? (int) round($withAnyApplied / $withSuggestions * 100)
            : 0;

        return [
            'adoption' => compact('withSuggestions', 'withAnyApplied', 'adoptionRate'),
            'fieldAdoption' => $fieldStats,
            'details' => $details,
        ];
    }

    /**
     * @return array{
     *   newUsersCount: int,
     *   kr1Count: int,
     *   kr1Percentage: int,
     *   kr2Count: int,
     *   kr2Percentage: int,
     *   kr3SentCount: int,
     *   kr3RespondedCount: int,
     *   kr3Percentage: int|null,
     *   rangeLabel: string,
     * }
     */
    private function onboardingStats(string $range): array
    {
        [$cohortStart, $rangeLabel] = match ($range) {
            'week' => [now()->subDays(7), 'laatste week'],
            'quarter' => [now()->subDays(90), 'laatste 3 maanden'],
            'alltime' => [null, 'sinds start'],
            default => [now()->subDays(30), 'laatste maand'],
        };

        // Cohort = users whose account was created within the window (not email_verified_at,
        // which was bulk-reset for legacy users and is unreliable as a "joined" signal).
        $newUsers = User::query()
            ->whereNotNull('email_verified_at')
            ->when($cohortStart !== null, fn ($q) => $q->where('created_at', '>=', $cohortStart))
            ->where('role', '!=', 'admin')
            ->get(['id', 'email_verified_at', 'first_return_at']);

        $newUsersCount = $newUsers->count();
        $newUserIds = $newUsers->pluck('id');

        // KR1: returned within 7 days of email verification
        $kr1Count = $newUsers->filter(fn ($u) => $u->getRawOriginal('first_return_at') !== null)->count();
        $kr1Percentage = $newUsersCount > 0 ? (int) round($kr1Count / $newUsersCount * 100) : 0;

        // KR2: gave ≥1 kudos OR placed ≥1 comment within 30d of registration.
        // Use keyBy for O(1) lookup instead of firstWhere's O(n) linear scan.
        $usersById = $newUsers->keyBy('id');

        // Outer SQL bounds derived from the cohort's verification dates so the
        // database can index-scan instead of pulling every comment ever.
        [$cohortLowerBound, $cohortUpperBound] = $this->cohortInteractionBounds($newUsers);

        $usersWithKudos = Like::query()
            ->whereIn('user_id', $newUserIds)
            ->where('type', 'kudos')
            ->whereRaw('created_at >= (SELECT email_verified_at FROM users WHERE users.id = likes.user_id)')
            ->get(['user_id', 'created_at'])
            ->filter(function ($like) use ($usersById) {
                $user = $usersById->get($like->user_id);
                if (! $user) {
                    return false;
                }

                return $like->created_at->diffInDays($user->email_verified_at) <= 30;
            })
            ->pluck('user_id')
            ->unique();

        $usersWithComment = Comment::query()
            ->whereIn('user_id', $newUserIds)
            ->when($cohortLowerBound, fn ($q) => $q->where('created_at', '>=', $cohortLowerBound))
            ->when($cohortUpperBound, fn ($q) => $q->where('created_at', '<=', $cohortUpperBound))
            ->get(['user_id', 'created_at'])
            ->filter(function ($comment) use ($usersById) {
                $user = $usersById->get($comment->user_id);
                if (! $user) {
                    return false;
                }

                return $comment->created_at->diffInDays($user->email_verified_at) <= 30;
            })
            ->pluck('user_id')
            ->unique();

        $kr2Count = $usersWithKudos->merge($usersWithComment)->unique()->count();
        $kr2Percentage = $newUsersCount > 0 ? (int) round($kr2Count / $newUsersCount * 100) : 0;

        // KR3: of all download follow-up emails sent, how many users then kudosed/commented on that fiche?
        $followUpLogs = OnboardingEmailLog::query()
            ->where('mail_key', 'like', 'download_followup_%')
            ->get(['user_id', 'mail_key', 'sent_at']);

        $kr3SentCount = $followUpLogs->count();
        $kr3RespondedCount = 0;

        if ($kr3SentCount > 0) {
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
                    $kr3RespondedCount++;
                }
            }
        }

        $kr3Percentage = $kr3SentCount > 0
            ? (int) round($kr3RespondedCount / $kr3SentCount * 100)
            : null;

        return compact(
            'newUsersCount',
            'kr1Count',
            'kr1Percentage',
            'kr2Count',
            'kr2Percentage',
            'kr3SentCount',
            'kr3RespondedCount',
            'kr3Percentage',
            'rangeLabel',
        );
    }

    /**
     * @return array<string, int>
     */
    private function onboardingEmailCounts(string $range): array
    {
        $since = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subDays(30),
        };

        $rows = OnboardingEmailLog::query()
            ->when($since !== null, fn ($q) => $q->where('sent_at', '>=', $since))
            ->get(['mail_key']);

        // mail_4 = first bookmark, mail_5 = milestone 10, mail_6 = milestone 50 (set by LikeObserver)
        // download_followup_* = per-fiche follow-up (aggregated as one key)
        $counts = [
            'mail_1' => 0,
            'mail_2' => 0,
            'mail_3' => 0,
            'download_followup' => 0,
            'mail_4' => 0,
            'mail_5' => 0,
            'mail_6' => 0,
        ];

        foreach ($rows as $row) {
            if (str_starts_with($row->mail_key, 'download_followup_')) {
                $counts['download_followup']++;
            } elseif (isset($counts[$row->mail_key])) {
                $counts[$row->mail_key]++;
            }
        }

        return $counts;
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function signupTrend(string $range): array
    {
        $base = $this->signupCohortQuery();

        return match ($range) {
            'week' => $this->signupTrendDaily($base, days: 7),
            'quarter' => $this->signupTrendWeekly($base),
            'alltime' => $this->signupTrendMonthly($base),
            default => $this->signupTrendDaily($base, days: 30),
        };
    }

    /** @return Builder<User> */
    private function signupCohortQuery(): Builder
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be');
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function signupTrendDaily(Builder $base, int $days): array
    {
        $signups = (clone $base)
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['created_at']);

        $grouped = [];
        foreach ($signups as $signup) {
            $key = $signup->created_at->format('Y-m-d');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $result[] = [
                'key' => $key,
                'label' => $date->isoFormat('D MMM'),
                'count' => $grouped[$key] ?? 0,
            ];
        }

        return $result;
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function signupTrendWeekly(Builder $base): array
    {
        $signups = (clone $base)
            ->where('created_at', '>=', now()->subWeeks(12)->startOfWeek())
            ->get(['created_at']);

        $grouped = [];
        foreach ($signups as $signup) {
            $key = $signup->created_at->format('oW');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        for ($i = 12; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = $date->format('oW');
            $result[] = [
                'key' => $key,
                'label' => $date->isoFormat('D MMM'),
                'count' => $grouped[$key] ?? 0,
            ];
        }

        return $result;
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function signupTrendMonthly(Builder $base): array
    {
        $signups = (clone $base)->get(['created_at']);

        if ($signups->isEmpty()) {
            return [];
        }

        $earliest = $signups->min('created_at')->copy()->startOfMonth();
        $end = now()->startOfMonth();

        $grouped = [];
        foreach ($signups as $signup) {
            $key = $signup->created_at->format('Y-m');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        $cursor = $earliest->copy();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $result[] = [
                'key' => $key,
                'label' => $cursor->isoFormat('MMM YYYY'),
                'count' => $grouped[$key] ?? 0,
            ];
            $cursor->addMonth();
        }

        return $result;
    }

    /** @return array<int, array{key: string, label: string, downloads: int, thanked: int, rate: int}> */
    private function thankTrend(string $range): array
    {
        return match ($range) {
            'week' => $this->thankTrendDaily(days: 7),
            'quarter' => $this->thankTrendWeekly(),
            'alltime' => $this->thankTrendMonthly(),
            default => $this->thankTrendDaily(days: 30),
        };
    }

    /** @return array<int, array{key: string, label: string, downloads: int, thanked: int, rate: int}> */
    private function thankTrendDaily(int $days): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $rows = $this->computeThankedDownloads($since);

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['downloaded_at']->format('Y-m-d');
            $grouped[$key]['downloads'] = ($grouped[$key]['downloads'] ?? 0) + 1;
            $grouped[$key]['thanked'] = ($grouped[$key]['thanked'] ?? 0) + ($row['is_thanked'] ? 1 : 0);
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $downloads = $grouped[$key]['downloads'] ?? 0;
            $thanked = $grouped[$key]['thanked'] ?? 0;
            $result[] = [
                'key' => $key,
                'label' => $date->isoFormat('D MMM'),
                'downloads' => $downloads,
                'thanked' => $thanked,
                'rate' => $downloads > 0 ? (int) round($thanked / $downloads * 100) : 0,
            ];
        }

        return $result;
    }

    /** @return array<int, array{key: string, label: string, downloads: int, thanked: int, rate: int}> */
    private function thankTrendWeekly(): array
    {
        $since = now()->subWeeks(12)->startOfWeek();
        $rows = $this->computeThankedDownloads($since);

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['downloaded_at']->format('oW');
            $grouped[$key]['downloads'] = ($grouped[$key]['downloads'] ?? 0) + 1;
            $grouped[$key]['thanked'] = ($grouped[$key]['thanked'] ?? 0) + ($row['is_thanked'] ? 1 : 0);
        }

        $result = [];
        for ($i = 12; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = $date->format('oW');
            $downloads = $grouped[$key]['downloads'] ?? 0;
            $thanked = $grouped[$key]['thanked'] ?? 0;
            $result[] = [
                'key' => $key,
                'label' => $date->isoFormat('D MMM'),
                'downloads' => $downloads,
                'thanked' => $thanked,
                'rate' => $downloads > 0 ? (int) round($thanked / $downloads * 100) : 0,
            ];
        }

        return $result;
    }

    /** @return array<int, array{key: string, label: string, downloads: int, thanked: int, rate: int}> */
    private function thankTrendMonthly(): array
    {
        $rows = $this->computeThankedDownloads(null);

        if ($rows->isEmpty()) {
            return [];
        }

        $earliest = $rows->min('downloaded_at')->copy()->startOfMonth();
        $end = now()->startOfMonth();

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['downloaded_at']->format('Y-m');
            $grouped[$key]['downloads'] = ($grouped[$key]['downloads'] ?? 0) + 1;
            $grouped[$key]['thanked'] = ($grouped[$key]['thanked'] ?? 0) + ($row['is_thanked'] ? 1 : 0);
        }

        $result = [];
        $cursor = $earliest->copy();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $downloads = $grouped[$key]['downloads'] ?? 0;
            $thanked = $grouped[$key]['thanked'] ?? 0;
            $result[] = [
                'key' => $key,
                'label' => $cursor->isoFormat('MMM YYYY'),
                'downloads' => $downloads,
                'thanked' => $thanked,
                'rate' => $downloads > 0 ? (int) round($thanked / $downloads * 100) : 0,
            ];
            $cursor->addMonth();
        }

        return $result;
    }

    /**
     * @return array{
     *   currentRate: int,
     *   previousRate: int|null,
     *   delta: int|null,
     *   currentDownloads: int,
     *   currentThanked: int,
     *   rangeLabel: string,
     *   lowData: bool,
     *   totalThankedAllTime: int,
     *   kudosThankCount: int,
     *   commentThankCount: int,
     * }
     */
    private function thankStats(string $range): array
    {
        $rangeLabel = match ($range) {
            'week' => 'deze week',
            'quarter' => 'deze 3 maanden',
            'alltime' => 'sinds start',
            default => 'deze maand',
        };

        if ($range === 'alltime') {
            $current = $this->computeThankedDownloads(null);
            $previous = collect();
        } else {
            $currentStart = match ($range) {
                'quarter' => now()->subWeeks(12)->startOfWeek(),
                'week' => now()->subDays(6)->startOfDay(),
                default => now()->subDays(29)->startOfDay(),
            };
            $previousStart = match ($range) {
                'quarter' => now()->subWeeks(24)->startOfWeek(),
                'week' => now()->subDays(13)->startOfDay(),
                default => now()->subDays(59)->startOfDay(),
            };

            $all = $this->computeThankedDownloads($previousStart);
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
        $delta = $previousRate !== null ? $currentRate - $previousRate : null;

        $kudosThankCount = $current->filter(fn ($row) => $row['thanked_via_kudos'])->count();
        $commentThankCount = $current->filter(fn ($row) => $row['thanked_via_comment'])->count();

        $totalThankedAllTime = $range === 'alltime'
            ? $currentThanked
            : $this->computeThankedDownloads(null)->filter(fn ($row) => $row['is_thanked'])->count();

        return [
            'currentRate' => $currentRate,
            'previousRate' => $previousRate,
            'delta' => $delta,
            'currentDownloads' => $currentDownloads,
            'currentThanked' => $currentThanked,
            'rangeLabel' => $rangeLabel,
            'lowData' => $currentDownloads > 0 && $currentDownloads < 5,
            'totalThankedAllTime' => $totalThankedAllTime,
            'kudosThankCount' => $kudosThankCount,
            'commentThankCount' => $commentThankCount,
        ];
    }

    /**
     * Returns one row per (user, fiche) download pair, annotated with whether
     * the user later thanked that fiche (post-download kudos OR comment).
     *
     * The full all-time dataset is fetched once per request and cached on the
     * instance; sub-range calls filter that cache in PHP. This avoids the
     * 3 separate SQL trips that previously fired on the bedankjes tab.
     *
     * @param  ?Carbon  $since  Null means all downloads; otherwise only those with created_at >= $since.
     * @return Collection<int, array{
     *   user_id: int,
     *   fiche_id: int,
     *   downloaded_at: Carbon,
     *   thanked_via_kudos: bool,
     *   thanked_via_comment: bool,
     *   is_thanked: bool,
     * }>
     */
    /**
     * Outer [from, to] bounds for kudos/comment lookups scoped to a cohort whose
     * relevant window is ±30 days around each user's email_verified_at. Returns
     * [null, null] for empty cohorts. The precise per-user 30-day check still
     * happens in PHP — these bounds only narrow the SQL scan.
     *
     * @param  Collection<int, User>  $cohort
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function cohortInteractionBounds(Collection $cohort): array
    {
        if ($cohort->isEmpty()) {
            return [null, null];
        }

        $min = $cohort->min('email_verified_at');
        $max = $cohort->max('email_verified_at');

        return [
            $min?->copy()->subDays(30),
            $max?->copy()->addDays(30),
        ];
    }

    private function computeThankedDownloads(?Carbon $since): Collection
    {
        if ($this->allThankedDownloads === null) {
            $this->allThankedDownloads = $this->fetchAllThankedDownloads();
        }

        if ($since === null) {
            return $this->allThankedDownloads;
        }

        return $this->allThankedDownloads
            ->filter(fn ($row) => $row['downloaded_at'] >= $since)
            ->values();
    }

    /**
     * @return Collection<int, array{
     *   user_id: int,
     *   fiche_id: int,
     *   downloaded_at: Carbon,
     *   thanked_via_kudos: bool,
     *   thanked_via_comment: bool,
     *   is_thanked: bool,
     * }>
     */
    private function fetchAllThankedDownloads(): Collection
    {
        $downloads = UserInteraction::query()
            ->where('interactable_type', Fiche::class)
            ->where('type', 'download')
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
                'thanked_via_kudos' => $thankedViaKudos,
                'thanked_via_comment' => $thankedViaComment,
                'is_thanked' => $thankedViaKudos || $thankedViaComment,
            ];
        });
    }

    /**
     * @return array{
     *   currentCount: int,
     *   previousCount: int|null,
     *   delta: int|null,
     *   rangeLabel: string,
     *   cohortCount: int,
     *   verifiedCount: int,
     *   verificationRate: int,
     *   verificationLowData: bool,
     *   totalMembers: int,
     * }
     */
    private function signupStats(string $range): array
    {
        $base = $this->signupCohortQuery();

        [$windowDays, $rangeLabel] = match ($range) {
            'week' => [7, 'deze week'],
            'quarter' => [90, 'deze 3 maanden'],
            'alltime' => [null, 'sinds start'],
            default => [30, 'deze maand'],
        };

        if ($windowDays === null) {
            $row = (clone $base)
                ->selectRaw(
                    'COUNT(*) AS total_members,
                     SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) AS verified_count'
                )
                ->first();

            $totalMembers = (int) $row->total_members;
            $verifiedCount = (int) $row->verified_count;
            $currentCount = $totalMembers;
            $previousCount = null;
            $delta = null;
        } else {
            $currentStart = match ($range) {
                'quarter' => now()->subWeeks(12)->startOfWeek(),
                'week' => now()->subDays(6)->startOfDay(),
                default => now()->subDays(29)->startOfDay(),
            };
            $previousStart = match ($range) {
                'quarter' => now()->subWeeks(24)->startOfWeek(),
                'week' => now()->subDays(13)->startOfDay(),
                default => now()->subDays(59)->startOfDay(),
            };

            $row = (clone $base)
                ->selectRaw(
                    'COUNT(*) AS total_members,
                     SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS current_count,
                     SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS previous_count,
                     SUM(CASE WHEN created_at >= ? AND email_verified_at IS NOT NULL THEN 1 ELSE 0 END) AS verified_count',
                    [$currentStart, $previousStart, $currentStart, $currentStart]
                )
                ->first();

            $totalMembers = (int) $row->total_members;
            $currentCount = (int) $row->current_count;
            $previousCount = (int) $row->previous_count;
            $verifiedCount = (int) $row->verified_count;
            $delta = $currentCount - $previousCount;
        }

        $cohortCount = $currentCount;
        $verificationRate = $cohortCount > 0 ? (int) round($verifiedCount / $cohortCount * 100) : 0;
        $verificationLowData = $cohortCount > 0 && $cohortCount < 5;

        return compact(
            'currentCount',
            'previousCount',
            'delta',
            'rangeLabel',
            'cohortCount',
            'verifiedCount',
            'verificationRate',
            'verificationLowData',
            'totalMembers',
        );
    }

    /** @return Builder<OnboardingEmailLog> */
    private function newsletterSendsQuery(): Builder
    {
        return OnboardingEmailLog::query()
            ->where('mail_key', 'LIKE', 'newsletter-cycle-%')
            ->whereHas('user', fn ($q) => $q
                ->where('role', '!=', 'admin')
                ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            );
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function newsletterTrend(string $range): array
    {
        $base = $this->newsletterSendsQuery();

        return match ($range) {
            'week' => $this->newsletterTrendDaily($base, days: 7),
            'quarter' => $this->newsletterTrendWeekly($base),
            'alltime' => $this->newsletterTrendMonthly($base),
            default => $this->newsletterTrendDaily($base, days: 30),
        };
    }

    /**
     * @param  Builder<OnboardingEmailLog>  $base
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function newsletterTrendDaily(Builder $base, int $days): array
    {
        $sends = (clone $base)
            ->where('sent_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['sent_at']);

        $grouped = [];
        foreach ($sends as $send) {
            $key = $send->sent_at->format('Y-m-d');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $result[] = [
                'key' => $key,
                'label' => $date->isoFormat('D MMM'),
                'count' => $grouped[$key] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * @param  Builder<OnboardingEmailLog>  $base
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function newsletterTrendWeekly(Builder $base): array
    {
        $sends = (clone $base)
            ->where('sent_at', '>=', now()->subWeeks(12)->startOfWeek())
            ->get(['sent_at']);

        $grouped = [];
        foreach ($sends as $send) {
            $key = $send->sent_at->format('oW');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        for ($i = 12; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = $date->format('oW');
            $result[] = [
                'key' => $key,
                'label' => $date->isoFormat('D MMM'),
                'count' => $grouped[$key] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * @param  Builder<OnboardingEmailLog>  $base
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function newsletterTrendMonthly(Builder $base): array
    {
        $sends = (clone $base)->get(['sent_at']);

        if ($sends->isEmpty()) {
            return [];
        }

        $earliest = $sends->min('sent_at')->copy()->startOfMonth();
        $end = now()->startOfMonth();

        $grouped = [];
        foreach ($sends as $send) {
            $key = $send->sent_at->format('Y-m');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        $cursor = $earliest->copy();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $result[] = [
                'key' => $key,
                'label' => $cursor->isoFormat('MMM YYYY'),
                'count' => $grouped[$key] ?? 0,
            ];
            $cursor->addMonth();
        }

        return $result;
    }

    /**
     * @return array{
     *   currentSent: int,
     *   previousSent: int|null,
     *   delta: int|null,
     *   totalSubscribers: int,
     *   rangeLabel: string,
     * }
     */
    private function newsletterStats(string $range): array
    {
        $base = $this->newsletterSendsQuery();

        [$windowDays, $rangeLabel] = match ($range) {
            'week' => [7, 'deze week'],
            'quarter' => [90, 'deze 3 maanden'],
            'alltime' => [null, 'sinds start'],
            default => [30, 'deze maand'],
        };

        if ($windowDays === null) {
            $currentSent = (clone $base)->count();
            $previousSent = null;
            $delta = null;
        } else {
            $currentStart = match ($range) {
                'quarter' => now()->subWeeks(12)->startOfWeek(),
                'week' => now()->subDays(6)->startOfDay(),
                default => now()->subDays(29)->startOfDay(),
            };
            $previousStart = match ($range) {
                'quarter' => now()->subWeeks(24)->startOfWeek(),
                'week' => now()->subDays(13)->startOfDay(),
                default => now()->subDays(59)->startOfDay(),
            };

            $row = (clone $base)
                ->selectRaw(
                    'SUM(CASE WHEN sent_at >= ? THEN 1 ELSE 0 END) AS current_sent,
                     SUM(CASE WHEN sent_at >= ? AND sent_at < ? THEN 1 ELSE 0 END) AS previous_sent',
                    [$currentStart, $previousStart, $currentStart]
                )
                ->first();

            $currentSent = (int) $row->current_sent;
            $previousSent = (int) $row->previous_sent;
            $delta = $currentSent - $previousSent;
        }

        $totalSubscribers = User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('newsletter_unsubscribed_at')
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            ->count();

        return compact('currentSent', 'previousSent', 'delta', 'totalSubscribers', 'rangeLabel');
    }

    /**
     * Per-cycle bucket: % of recipients who unsubscribed within 7 days of receiving that cycle.
     *
     * @return array<string, array{label: string, sent: int, unsubscribed: int, rate: int, lowData: bool}>
     */
    private function unsubscribeByCycle(string $range): array
    {
        $cutoff = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subDays(30),
        };

        $sends = $this->newsletterSendsQuery()
            ->when($cutoff !== null, fn ($q) => $q->where('sent_at', '>=', $cutoff))
            ->with('user:id,newsletter_unsubscribed_at')
            ->get(['user_id', 'mail_key', 'sent_at']);

        $buckets = [
            'cycle1' => ['label' => 'Cyclus 1', 'sent' => 0, 'unsubscribed' => 0],
            'cycle2' => ['label' => 'Cyclus 2', 'sent' => 0, 'unsubscribed' => 0],
            'cycle3' => ['label' => 'Cyclus 3', 'sent' => 0, 'unsubscribed' => 0],
            'cycle4plus' => ['label' => 'Cyclus 4+', 'sent' => 0, 'unsubscribed' => 0],
        ];

        foreach ($sends as $send) {
            if (! $send->user) {
                continue;
            }

            $cycle = (int) str_replace('newsletter-cycle-', '', $send->mail_key);
            $key = match (true) {
                $cycle === 1 => 'cycle1',
                $cycle === 2 => 'cycle2',
                $cycle === 3 => 'cycle3',
                default => 'cycle4plus',
            };

            $buckets[$key]['sent']++;

            $unsubAt = $send->user->newsletter_unsubscribed_at;
            if ($unsubAt
                && $unsubAt->greaterThanOrEqualTo($send->sent_at)
                && $unsubAt->lessThanOrEqualTo($send->sent_at->copy()->addDays(7))
            ) {
                $buckets[$key]['unsubscribed']++;
            }
        }

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['rate'] = $bucket['sent'] > 0
                ? (int) round($bucket['unsubscribed'] / $bucket['sent'] * 100)
                : 0;
            $buckets[$key]['lowData'] = $bucket['sent'] > 0 && $bucket['sent'] < 5;
        }

        return $buckets;
    }

    /**
     * Of newsletter sends in the period, how many recipients had a site visit
     * within 7 days after receiving the newsletter? Proxies "did the newsletter
     * bring them back" — but counts any visit, not just newsletter clicks.
     *
     * @return array{
     *   sent: int,
     *   activated: int,
     *   rate: int,
     *   rangeLabel: string,
     *   lowData: bool,
     * }
     */
    private function activationStats(string $range): array
    {
        $cutoff = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subDays(30),
        };

        $rangeLabel = match ($range) {
            'week' => 'deze week',
            'quarter' => 'deze 3 maanden',
            'alltime' => 'sinds start',
            default => 'deze maand',
        };

        $sends = $this->newsletterSendsQuery()
            ->when($cutoff !== null, fn ($q) => $q->where('sent_at', '>=', $cutoff))
            ->with('user:id,last_visited_at')
            ->get(['user_id', 'sent_at']);

        $sent = $sends->count();
        $activated = 0;

        foreach ($sends as $send) {
            $lastVisited = $send->user?->last_visited_at;
            if (! $lastVisited) {
                continue;
            }

            if ($lastVisited->greaterThanOrEqualTo($send->sent_at)
                && $lastVisited->lessThanOrEqualTo($send->sent_at->copy()->addDays(7))
            ) {
                $activated++;
            }
        }

        $rate = $sent > 0 ? (int) round($activated / $sent * 100) : 0;

        return [
            'sent' => $sent,
            'activated' => $activated,
            'rate' => $rate,
            'rangeLabel' => $rangeLabel,
            'lowData' => $sent > 0 && $sent < 5,
        ];
    }

    /**
     * Forecast: how many newsletter sends will fire in the next 30 days,
     * broken down by cycle bucket. Mirrors SendMonthlyCohortNewsletter logic
     * (30-day anniversary + grace window for cycles 1–3 + 6-month dormancy
     * gate for cycles 4+).
     *
     * Each user has at most one anniversary in the 30-day window. We compute
     * `daysAhead` in [0,29] such that `(today + daysAhead - created)` is a
     * multiple of 30 — namely `(30 - D mod 30) mod 30`. Users created today
     * (D=0) land on day 30, just outside the window, so they're filtered in SQL.
     *
     * @return array{
     *   total: int,
     *   buckets: array<string, array{label: string, count: int}>,
     *   windowDays: int,
     * }
     */
    private function upcomingNewsletterSends(): array
    {
        return Cache::remember(
            'admin:dashboard:upcoming-newsletter',
            now()->addMinutes(5),
            fn () => $this->computeUpcomingNewsletterSends()
        );
    }

    /**
     * @return array{
     *   total: int,
     *   buckets: array<string, array{label: string, count: int}>,
     *   windowDays: int,
     * }
     */
    private function computeUpcomingNewsletterSends(): array
    {
        $windowDays = 30;
        $today = now()->startOfDay();

        $candidates = User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('newsletter_unsubscribed_at')
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            ->where('created_at', '<', $today)
            ->get(['id', 'created_at', 'last_visited_at']);

        $buckets = [
            'cycle1' => ['label' => 'Cyclus 1', 'count' => 0],
            'cycle2' => ['label' => 'Cyclus 2', 'count' => 0],
            'cycle3' => ['label' => 'Cyclus 3', 'count' => 0],
            'cycle4plus' => ['label' => 'Cyclus 4+', 'count' => 0],
        ];

        $total = 0;
        $todayTs = $today->getTimestamp();

        foreach ($candidates as $user) {
            $createdTs = $user->created_at->copy()->startOfDay()->getTimestamp();
            $d = intdiv($todayTs - $createdTs, 86400);

            // A user with d=0 was filtered in SQL; defensive guard for DST/edge precision.
            if ($d < 1) {
                continue;
            }

            $modR = $d % 30;
            $a = $modR === 0 ? 0 : 30 - $modR;
            $cycle = intdiv($d + $a, 30);

            if ($cycle >= 4) {
                $fireDate = $today->copy()->addDays($a);
                $lastActive = $user->last_visited_at ?? $user->created_at;
                if ($lastActive->lessThan($fireDate->copy()->subMonths(6))) {
                    continue;
                }
            }

            $key = match (true) {
                $cycle === 1 => 'cycle1',
                $cycle === 2 => 'cycle2',
                $cycle === 3 => 'cycle3',
                default => 'cycle4plus',
            };
            $buckets[$key]['count']++;
            $total++;
        }

        return [
            'total' => $total,
            'buckets' => $buckets,
            'windowDays' => $windowDays,
        ];
    }

    /**
     * @return array{cycle: int, sent_at: Carbon, recipients: int}|null
     */
    private function lastNewsletterDigestMeta(): ?array
    {
        $log = OnboardingEmailLog::query()
            ->where('mail_key', 'LIKE', 'newsletter-cycle-%')
            ->latest('sent_at')
            ->first(['mail_key', 'sent_at']);

        if (! $log) {
            return null;
        }

        $cycle = (int) str_replace('newsletter-cycle-', '', $log->mail_key);
        $recipients = OnboardingEmailLog::query()
            ->where('mail_key', $log->mail_key)
            ->where('sent_at', '>=', $log->sent_at->copy()->subHours(6))
            ->count();

        return [
            'cycle' => $cycle,
            'sent_at' => $log->sent_at,
            'recipients' => $recipients,
        ];
    }

    /**
     * @return array<int, array{title: string, meta: string}>
     */
    private function recentUnsubscribes(): array
    {
        $users = User::query()
            ->whereNotNull('newsletter_unsubscribed_at')
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            ->latest('newsletter_unsubscribed_at')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'newsletter_unsubscribed_at']);

        if ($users->isEmpty()) {
            return [];
        }

        // Find each user's last received newsletter-cycle BEFORE their unsubscribe.
        $userIds = $users->pluck('id');
        $logs = OnboardingEmailLog::query()
            ->whereIn('user_id', $userIds)
            ->where('mail_key', 'LIKE', 'newsletter-cycle-%')
            ->get(['user_id', 'mail_key', 'sent_at']);

        $lastCycleByUser = [];
        foreach ($users as $user) {
            $userLogs = $logs
                ->where('user_id', $user->id)
                ->filter(fn ($log) => $log->sent_at <= $user->newsletter_unsubscribed_at)
                ->sortByDesc('sent_at');
            $latestLog = $userLogs->first();
            $lastCycleByUser[$user->id] = $latestLog
                ? (int) str_replace('newsletter-cycle-', '', $latestLog->mail_key)
                : null;
        }

        return $users->map(function ($user) use ($lastCycleByUser) {
            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            $cycle = $lastCycleByUser[$user->id];
            $meta = $cycle !== null
                ? "na cyclus {$cycle} · ".$user->newsletter_unsubscribed_at->diffForHumans()
                : $user->newsletter_unsubscribed_at->diffForHumans();

            return [
                'title' => $name !== '' ? $name : $user->email,
                'meta' => $meta,
            ];
        })->all();
    }

    /**
     * @return array<int, array{name: string, signed_up_at: string, verified: bool, returned_7d: bool, interacted_30d: bool, followup_received: bool}>
     */
    private function recentSignupsFunnel(): array
    {
        $users = User::query()
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            ->whereNotNull('email_verified_at')
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at', 'email_verified_at', 'first_return_at']);

        if ($users->isEmpty()) {
            return [];
        }

        $userIds = $users->pluck('id');
        $usersById = $users->keyBy('id');

        [$cohortLowerBound, $cohortUpperBound] = $this->cohortInteractionBounds($users);

        $kudosUsers = Like::query()
            ->whereIn('user_id', $userIds)
            ->where('type', 'kudos')
            ->whereRaw('created_at >= (SELECT email_verified_at FROM users WHERE users.id = likes.user_id)')
            ->get(['user_id', 'created_at'])
            ->filter(function ($like) use ($usersById) {
                $user = $usersById->get($like->user_id);

                return $user && $like->created_at->diffInDays($user->email_verified_at) <= 30;
            })
            ->pluck('user_id');

        $commentUsers = Comment::query()
            ->whereIn('user_id', $userIds)
            ->when($cohortLowerBound, fn ($q) => $q->where('created_at', '>=', $cohortLowerBound))
            ->when($cohortUpperBound, fn ($q) => $q->where('created_at', '<=', $cohortUpperBound))
            ->get(['user_id', 'created_at'])
            ->filter(function ($comment) use ($usersById) {
                $user = $usersById->get($comment->user_id);

                return $user && $comment->created_at->diffInDays($user->email_verified_at) <= 30;
            })
            ->pluck('user_id');

        $interactingUserIds = $kudosUsers->merge($commentUsers)->unique();

        $followupUserIds = OnboardingEmailLog::query()
            ->whereIn('user_id', $userIds)
            ->where('mail_key', 'LIKE', 'download_followup_%')
            ->pluck('user_id')
            ->unique();

        return $users->map(function ($user) use ($interactingUserIds, $followupUserIds) {
            $name = trim(($user->first_name ?? '').' '.substr($user->last_name ?? '', 0, 1));

            return [
                'name' => $name !== '' ? $name.($user->last_name ? '.' : '') : $user->email,
                'signed_up_at' => $user->created_at->isoFormat('D MMM'),
                'verified' => $user->email_verified_at !== null,
                'returned_7d' => $user->getRawOriginal('first_return_at') !== null,
                'interacted_30d' => $interactingUserIds->contains($user->id),
                'followup_received' => $followupUserIds->contains($user->id),
            ];
        })->all();
    }

    /**
     * @return array<int, array{title: string, meta: string}>
     */
    private function stalledVerifications(): array
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            ->whereNull('email_verified_at')
            ->where('created_at', '<', now()->subDays(7))
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at'])
            ->map(function ($user) {
                $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $daysAgo = (int) round($user->created_at->diffInDays(now()));

                return [
                    'title' => $name !== '' ? $name : $user->email,
                    'meta' => $daysAgo.' dagen geleden aangemeld',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{title: string, url: string, meta: string, body: string}>
     */
    private function recentThankComments(): array
    {
        // Pull a candidate window of recent comments on fiches.
        // Then filter to those where the user downloaded the fiche BEFORE commenting.
        $candidates = Comment::query()
            ->where('commentable_type', Fiche::class)
            ->latest('created_at')
            ->limit(50)
            ->with([
                'user:id,first_name,last_name',
                'commentable:id,title,slug,initiative_id',
                'commentable.initiative:id,slug',
            ])
            ->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        $userIds = $candidates->pluck('user_id')->unique()->values()->all();
        $ficheIds = $candidates->pluck('commentable_id')->unique()->values()->all();

        $downloadsByPair = UserInteraction::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('interactable_id', $ficheIds)
            ->where('interactable_type', Fiche::class)
            ->where('type', 'download')
            ->get(['user_id', 'interactable_id', 'created_at'])
            ->groupBy(fn ($row) => $row->user_id.':'.$row->interactable_id)
            ->map(fn ($rows) => $rows->min('created_at'));

        return $candidates
            ->filter(function ($comment) use ($downloadsByPair) {
                $key = $comment->user_id.':'.$comment->commentable_id;
                $downloadAt = $downloadsByPair->get($key);

                return $downloadAt !== null && $downloadAt <= $comment->created_at;
            })
            ->take(5)
            ->map(function ($comment) {
                $fiche = $comment->commentable;
                $name = $comment->user?->first_name ?? 'iemand';

                return [
                    'title' => $fiche->title,
                    'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                    'meta' => $name.' · '.$comment->created_at->diffForHumans(),
                    'body' => $comment->body,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{title: string, url: string, badge: int}>
     */
    private function topThankedFiches(string $range, ?Carbon $since): array
    {
        return Cache::remember(
            "admin:dashboard:top-thanked:{$range}",
            now()->addMinutes(5),
            function () use ($since) {
                $thanked = $this->computeThankedDownloads($since)->filter(fn ($row) => $row['is_thanked']);

                if ($thanked->isEmpty()) {
                    return [];
                }

                $countsByFiche = $thanked
                    ->groupBy('fiche_id')
                    ->map(fn ($rows) => $rows->count())
                    ->sortDesc()
                    ->take(5);

                $fiches = Fiche::query()
                    ->with('initiative:id,slug')
                    ->whereIn('id', $countsByFiche->keys()->all())
                    ->get(['id', 'title', 'slug', 'initiative_id'])
                    ->keyBy('id');

                return $countsByFiche
                    ->map(function ($count, $ficheId) use ($fiches) {
                        $fiche = $fiches->get($ficheId);
                        if (! $fiche) {
                            return null;
                        }

                        return [
                            'title' => $fiche->title,
                            'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                            'badge' => $count,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * @return array<int, array{title: string, url: string, badge: int, badge_color: string}>
     */
    private function lowestScoredFiches(): array
    {
        return Fiche::query()
            ->published()
            ->whereNotNull('presentation_score')
            ->orderBy('presentation_score')
            ->limit(5)
            ->with('initiative:id,slug')
            ->get(['id', 'title', 'slug', 'initiative_id', 'presentation_score'])
            ->map(fn ($fiche) => [
                'title' => $fiche->title,
                'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                'badge' => $fiche->presentation_score,
                'badge_color' => $this->scoreColor($fiche->presentation_score),
            ])
            ->all();
    }

    private function scoreColor(int $score): string
    {
        return match (true) {
            $score >= 70 => 'text-green-700',
            $score >= 40 => 'text-amber-600',
            default => 'text-red-600',
        };
    }

    /**
     * @return array<int, array{title: string, url: string, meta: string}>
     */
    private function recentAiSuggestionAcceptances(): array
    {
        $fieldLabels = [
            'title' => 'Titel',
            'description' => 'Omschrijving',
            'preparation' => 'Voorbereiding',
            'inventory' => 'Benodigdheden',
            'process' => 'Werkwijze',
        ];

        return Fiche::query()
            ->published()
            ->whereJsonLength('ai_suggestions->applied', '>', 0)
            ->latest('updated_at')
            ->limit(5)
            ->with('initiative:id,slug')
            ->get(['id', 'title', 'slug', 'initiative_id', 'ai_suggestions', 'updated_at'])
            ->map(function ($fiche) use ($fieldLabels) {
                $applied = $fiche->ai_suggestions['applied'] ?? [];
                $labels = collect($applied)
                    ->map(fn ($field) => $fieldLabels[$field] ?? $field)
                    ->take(3)
                    ->implode(' · ');
                $more = count($applied) > 3 ? ' +'.(count($applied) - 3) : '';

                return [
                    'title' => $fiche->title,
                    'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                    'meta' => $labels.$more.' overgenomen',
                ];
            })
            ->all();
    }
}
