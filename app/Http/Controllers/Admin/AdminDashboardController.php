<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $tab = request()->get('tab', 'presentatiekwaliteit');
        if (! in_array($tab, ['presentatiekwaliteit', 'onboarding', 'aanmeldingen'], true)) {
            $tab = 'presentatiekwaliteit';
        }

        $defaultRange = $tab === 'aanmeldingen' ? 'month' : 'week';
        $range = request()->get('range', $defaultRange);

        if ($tab === 'aanmeldingen' && ! in_array($range, ['month', 'quarter', 'alltime'], true)) {
            $range = 'month';
        }

        $cutoff = $range === 'week' ? now()->subDays(7) : now()->subWeeks(4);

        $weeklyTrend = $this->trend($range);
        $trendDelta = $this->trendDelta($weeklyTrend);
        $lastFiches = $this->lastFiches();
        $scored = $lastFiches->filter(fn ($f) => $f->presentation_score !== null);
        $lastFiveAvg = $scored->isNotEmpty() ? (int) round($scored->avg('presentation_score')) : null;
        $globalAvg = $this->globalAvg();
        $fichesWithSuggestions = Fiche::query()
            ->published()
            ->whereNotNull('ai_suggestions')
            ->where('created_at', '>=', $cutoff)
            ->with('initiative:id,slug')
            ->get(['id', 'title', 'slug', 'initiative_id', 'ai_suggestions']);
        $adoption = $this->adoptionStats($fichesWithSuggestions);
        $fieldAdoption = $this->fieldAdoption($fichesWithSuggestions);

        $signupTrend = $tab === 'aanmeldingen' ? $this->signupTrend($range) : [];

        return view('admin.dashboard', [
            'tab' => $tab,
            'range' => $range,
            'weeklyTrend' => $weeklyTrend,
            'trendDelta' => $trendDelta,
            'lastFiches' => $lastFiches,
            'lastFiveAvg' => $lastFiveAvg,
            'globalAvg' => $globalAvg,
            ...$adoption,
            'fieldAdoption' => $fieldAdoption,
            'ficheAdoptionDetails' => $this->ficheAdoptionDetails($fichesWithSuggestions),
            'onboardingStats' => $tab === 'onboarding' ? $this->onboardingStats() : [],
            'onboardingEmailCounts' => $tab === 'onboarding' ? $this->onboardingEmailCounts() : [],
            'signupTrend' => $signupTrend,
        ]);
    }

    /** @return array<int, array{week_key: string|int, week_label: string, avg_score: int|null}> */
    private function trend(string $range): array
    {
        return $range === 'week' ? $this->dailyTrend() : $this->monthlyTrend();
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
            $label = $date->format('d M');

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
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->where('quality_assessed_at', '>=', now()->subWeeks(4))
            ->get(['presentation_score', 'quality_assessed_at']);

        $grouped = [];
        foreach ($fiches as $fiche) {
            $date = $fiche->quality_assessed_at;
            $key = (int) $date->format('oW');
            $grouped[$key][] = $fiche->presentation_score;
        }

        $result = [];
        for ($i = 3; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = (int) $date->format('oW');
            $label = $date->format('d M');

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
        $avg = Fiche::query()
            ->published()
            ->whereNotNull('presentation_score')
            ->avg('presentation_score');

        return $avg !== null ? (int) round($avg) : null;
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

    /** @return array{withSuggestions: int, withAnyApplied: int, adoptionRate: int} */
    private function adoptionStats(Collection $fiches): array
    {
        $fields = ['title', 'description', 'preparation', 'inventory', 'process'];

        $withSuggestions = 0;
        $withAnyApplied = 0;

        foreach ($fiches as $fiche) {
            $suggestions = $fiche->ai_suggestions;
            $hasNonEmpty = collect($fields)->contains(
                fn ($field) => isset($suggestions[$field]) && $suggestions[$field] !== ''
            );

            if (! $hasNonEmpty) {
                continue;
            }

            $withSuggestions++;

            if (! empty($suggestions['applied'])) {
                $withAnyApplied++;
            }
        }

        $adoptionRate = $withSuggestions > 0
            ? (int) round($withAnyApplied / $withSuggestions * 100)
            : 0;

        return compact('withSuggestions', 'withAnyApplied', 'adoptionRate');
    }

    /**
     * @param  Collection<int, Fiche>  $fiches
     * @return array<int, array{title: string, url: string, fields: array<string, array{suggested: bool, applied: bool, label: string, shortLabel: string}>, adoptedCount: int, suggestedCount: int}>
     */
    private function ficheAdoptionDetails(Collection $fiches): array
    {
        $fieldMeta = [
            'title' => ['label' => 'Titel', 'shortLabel' => 'Titel'],
            'description' => ['label' => 'Omschrijving', 'shortLabel' => 'Omschr.'],
            'preparation' => ['label' => 'Voorbereiding', 'shortLabel' => 'Voorb.'],
            'inventory' => ['label' => 'Benodigdheden', 'shortLabel' => 'Ben.'],
            'process' => ['label' => 'Werkwijze', 'shortLabel' => 'Werkw.'],
        ];

        $result = [];

        foreach ($fiches as $fiche) {
            $suggestions = $fiche->ai_suggestions;
            $applied = $suggestions['applied'] ?? [];

            $fields = [];
            $suggestedCount = 0;
            $adoptedCount = 0;

            foreach ($fieldMeta as $key => $meta) {
                $suggested = isset($suggestions[$key]) && $suggestions[$key] !== '';
                $isApplied = in_array($key, $applied, true);

                if ($suggested) {
                    $suggestedCount++;
                }

                if ($suggested && $isApplied) {
                    $adoptedCount++;
                }

                $fields[$key] = [
                    'suggested' => $suggested,
                    'applied' => $isApplied,
                    'label' => $meta['label'],
                    'shortLabel' => $meta['shortLabel'],
                ];
            }

            if ($suggestedCount === 0) {
                continue;
            }

            $result[] = [
                'title' => $fiche->title,
                'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                'fields' => $fields,
                'adoptedCount' => $adoptedCount,
                'suggestedCount' => $suggestedCount,
            ];
        }

        return $result;
    }

    /** @return array<string, array{suggested: int, applied: int, rate: int, label: string}> */
    private function fieldAdoption(Collection $fiches): array
    {
        $fields = [
            'title' => 'Titel',
            'description' => 'Omschrijving',
            'preparation' => 'Voorbereiding',
            'inventory' => 'Benodigdheden',
            'process' => 'Werkwijze',
        ];

        $result = [];
        foreach ($fields as $field => $label) {
            $suggested = 0;
            $applied = 0;

            foreach ($fiches as $fiche) {
                $suggestions = $fiche->ai_suggestions;

                if (isset($suggestions[$field]) && $suggestions[$field] !== '') {
                    $suggested++;
                }

                if (in_array($field, $suggestions['applied'] ?? [], true)) {
                    $applied++;
                }
            }

            $rate = $suggested > 0 ? (int) round($applied / $suggested * 100) : 0;

            $result[$field] = compact('suggested', 'applied', 'rate') + ['label' => $label];
        }

        return $result;
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
     * }
     */
    private function onboardingStats(): array
    {
        $cohortStart = now()->subDays(30);

        // Cohort = users whose account was created within the window (not email_verified_at,
        // which was bulk-reset for legacy users and is unreliable as a "joined" signal).
        $newUsers = User::query()
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>=', $cohortStart)
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
        );
    }

    /**
     * @return array<string, int>
     */
    private function onboardingEmailCounts(): array
    {
        $since = now()->subDays(30);

        $rows = OnboardingEmailLog::query()
            ->where('sent_at', '>=', $since)
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
            'quarter' => $this->signupTrendWeekly($base),
            'alltime' => $this->signupTrendMonthly($base),
            default => $this->signupTrendDaily($base),
        };
    }

    private function signupCohortQuery(): Builder
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be');
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function signupTrendDaily(Builder $base): array
    {
        $signups = (clone $base)
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->get(['created_at']);

        $grouped = [];
        foreach ($signups as $signup) {
            $key = $signup->created_at->format('Y-m-d');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $result[] = [
                'key' => $key,
                'label' => $date->format('d M'),
                'count' => $grouped[$key] ?? 0,
            ];
        }

        return $result;
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    private function signupTrendWeekly(Builder $base): array
    {
        $signups = (clone $base)
            ->where('created_at', '>=', now()->subDays(90)->startOfDay())
            ->get(['created_at']);

        $grouped = [];
        foreach ($signups as $signup) {
            $key = (int) $signup->created_at->format('oW');
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        $result = [];
        for ($i = 12; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = (int) $date->format('oW');
            $result[] = [
                'key' => (string) $key,
                'label' => $date->format('d M'),
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
}
