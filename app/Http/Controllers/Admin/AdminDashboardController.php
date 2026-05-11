<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $tab = request()->get('tab', 'presentatiekwaliteit');
        if (! in_array($tab, ['presentatiekwaliteit', 'onboarding', 'aanmeldingen', 'bedankjes'], true)) {
            $tab = 'presentatiekwaliteit';
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
        $adoption = $this->adoptionStats($fichesWithSuggestions);
        $fieldAdoption = $this->fieldAdoption($fichesWithSuggestions);

        $signupTrend = $tab === 'aanmeldingen' ? $this->signupTrend($range) : [];
        $signupStats = $tab === 'aanmeldingen' ? $this->signupStats($range) : [];

        return view('admin.dashboard', [
            'tab' => $tab,
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'weeklyTrend' => $weeklyTrend,
            'trendDelta' => $trendDelta,
            'lastFiches' => $lastFiches,
            'lastFiveAvg' => $lastFiveAvg,
            'globalAvg' => $globalAvg,
            ...$adoption,
            'fieldAdoption' => $fieldAdoption,
            'ficheAdoptionDetails' => $this->ficheAdoptionDetails($fichesWithSuggestions),
            'onboardingStats' => $tab === 'onboarding' ? $this->onboardingStats($range) : [],
            'onboardingEmailCounts' => $tab === 'onboarding' ? $this->onboardingEmailCounts($range) : [],
            'signupTrend' => $signupTrend,
            'signupStats' => $signupStats,
            'thankTrend' => $tab === 'bedankjes' ? $this->thankTrend($range) : [],
            'thankStats' => $tab === 'bedankjes' ? $this->thankStats($range) : [],
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
    private function computeThankedDownloads(?Carbon $since): Collection
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

        // Earliest post-download kudos timestamp per (user, fiche)
        $kudosByPair = Like::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('likeable_id', $ficheIds)
            ->where('likeable_type', Fiche::class)
            ->where('type', 'kudos')
            ->where('count', '>', 0)
            ->get(['user_id', 'likeable_id', 'created_at'])
            ->groupBy(fn ($row) => $row->user_id.':'.$row->likeable_id)
            ->map(fn ($rows) => $rows->min('created_at'));

        // Earliest post-download comment timestamp per (user, fiche). soft-deleted is excluded by default scope.
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

        $currentStart = null;

        if ($windowDays === null) {
            $currentCount = (clone $base)->count();
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

            $currentCount = (clone $base)
                ->where('created_at', '>=', $currentStart)
                ->count();
            $previousCount = (clone $base)
                ->where('created_at', '>=', $previousStart)
                ->where('created_at', '<', $currentStart)
                ->count();
            $delta = $currentCount - $previousCount;
        }

        $cohortQuery = $currentStart === null
            ? clone $base
            : (clone $base)->where('created_at', '>=', $currentStart);
        $cohortCount = $currentCount;
        $verifiedCount = (clone $cohortQuery)->whereNotNull('email_verified_at')->count();
        $verificationRate = $cohortCount > 0 ? (int) round($verifiedCount / $cohortCount * 100) : 0;
        $verificationLowData = $cohortCount > 0 && $cohortCount < 5;

        $totalMembers = (clone $base)->count();

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
}
