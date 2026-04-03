<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $range = request()->get('range', 'week');
        $tab = request()->get('tab', 'presentatiekwaliteit');
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
            'onboardingStats' => $this->onboardingStats(),
            'onboardingEmailCounts' => $this->onboardingEmailCounts(),
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

        $newUsers = User::query()
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '>=', $cohortStart)
            ->where('role', '!=', 'admin')
            ->get(['id', 'email_verified_at', 'first_return_at']);

        $newUsersCount = $newUsers->count();
        $newUserIds = $newUsers->pluck('id');

        // KR1: returned within 7 days of email verification
        $kr1Count = $newUsers->whereNotNull('first_return_at')->count();
        $kr1Percentage = $newUsersCount > 0 ? (int) round($kr1Count / $newUsersCount * 100) : 0;

        // KR2: gave ≥1 kudos OR placed ≥1 comment within 30d of registration
        $usersWithKudos = Like::query()
            ->whereIn('user_id', $newUserIds)
            ->where('type', 'kudos')
            ->whereRaw('created_at >= (SELECT email_verified_at FROM users WHERE users.id = likes.user_id)')
            ->distinct()
            ->pluck('user_id');

        $usersWithComment = Comment::query()
            ->whereIn('user_id', $newUserIds)
            ->whereRaw('created_at >= (SELECT email_verified_at FROM users WHERE users.id = comments.user_id)')
            ->distinct()
            ->pluck('user_id');

        $kr2Count = $usersWithKudos->merge($usersWithComment)->unique()->count();
        $kr2Percentage = $newUsersCount > 0 ? (int) round($kr2Count / $newUsersCount * 100) : 0;

        // KR3: of all download follow-up emails sent, how many users then kudosed/commented on that fiche?
        $followUpLogs = OnboardingEmailLog::query()
            ->where('mail_key', 'like', 'download_followup_%')
            ->get(['user_id', 'mail_key', 'sent_at']);

        $kr3SentCount = $followUpLogs->count();
        $kr3RespondedCount = 0;

        foreach ($followUpLogs as $log) {
            $ficheId = (int) str_replace('download_followup_', '', $log->mail_key);

            $hasKudos = Like::query()
                ->where('user_id', $log->user_id)
                ->where('likeable_type', Fiche::class)
                ->where('likeable_id', $ficheId)
                ->where('type', 'kudos')
                ->where('created_at', '>=', $log->sent_at)
                ->exists();

            $hasComment = Comment::query()
                ->where('user_id', $log->user_id)
                ->where('commentable_type', Fiche::class)
                ->where('commentable_id', $ficheId)
                ->where('created_at', '>=', $log->sent_at)
                ->exists();

            if ($hasKudos || $hasComment) {
                $kr3RespondedCount++;
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
}
