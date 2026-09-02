<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Meet vier voorwaarden op de themakalender en mailt de beheerder alleen bij
 * een overschrijding, met een afkoelperiode per voorwaarde. De volledige
 * momentopname gaat naar de cache, waar /admin/gezondheid ze toont.
 *
 * @phpstan-type Snapshot array{
 *     checked_at: string,
 *     horizon_days: int|null,
 *     horizon_date: string|null,
 *     empty_upcoming: list<string>,
 *     drift: array{count: int, summary: string},
 *     watermark: string|null,
 *     fiches_after_watermark: int|null,
 *     exceeded: list<string>
 * }
 */
class CheckThemesHealth extends Command
{
    public const SNAPSHOT_KEY = 'themes:health-check:snapshot';

    public const ALERT_KEY_PREFIX = 'themes:health-check:alerted:';

    /** @var list<string> */
    public const CONDITIONS = ['horizon', 'empty_upcoming', 'drift', 'fiches_after_watermark'];

    protected $signature = 'themes:health-check';

    protected $description = 'Meet de themakalender en mail de beheerder alleen wanneer een drempel overschreden is';

    public function handle(): int
    {
        $path = (string) config('themes.file');

        if (! is_file($path)) {
            $this->error("Bestand niet gevonden: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! isset($data['themes']) || ! is_array($data['themes'])) {
            $this->error('JSON moet een top-level "themes" array bevatten.');

            return self::FAILURE;
        }

        $today = CarbonImmutable::today();
        $thresholds = (array) config('themes.health');

        $horizon = $this->horizon($today);
        $emptyUpcoming = $this->emptyUpcomingThemes($data['themes'], $today, (int) $thresholds['upcoming_window_days']);
        $drift = $this->drift($data['themes']);
        $watermark = $this->watermark($data);
        $fichesAfterWatermark = $watermark === null ? null : $this->fichesAfterWatermark($watermark);

        $problems = [];

        if ($horizon['days'] === null || $horizon['days'] < (int) $thresholds['min_horizon_days']) {
            $problems['horizon'] = $horizon['days'] === null
                ? 'Er staat geen enkele gelegenheid in de databank.'
                : sprintf(
                    'De laatste gelegenheid valt op %s, nog %d dagen vooruit (minimum %d). Draai themes:extend-occurrences --year=%d en daarna themes:import.',
                    $horizon['date'], $horizon['days'], (int) $thresholds['min_horizon_days'], $today->year + 1,
                );
        }

        if (count($emptyUpcoming) > (int) $thresholds['max_empty_upcoming']) {
            $problems['empty_upcoming'] = sprintf(
                "%d aankomend(e) thema('s) met koppelingen in themes.json maar nul gepubliceerde fiches: %s. Kijk na of die fiches geschrapt of gedepubliceerd zijn.",
                count($emptyUpcoming), implode(', ', $emptyUpcoming),
            );
        }

        if ($drift['count'] > (int) $thresholds['max_drift']) {
            $problems['drift'] = sprintf(
                'themes.json en de databank lopen uiteen: %s. Draai themes:import op productie.',
                $drift['summary'],
            );
        }

        if ($watermark === null) {
            $problems['fiches_after_watermark'] = 'themes.json heeft geen fiche_match_watermark. Zonder die datumstempel valt niet te zeggen hoeveel fiches nog geen koppelronde gezien hebben. Zet hem bovenaan het bestand op de datum van de laatste ronde.';
        } elseif ($fichesAfterWatermark > (int) $thresholds['max_fiches_after_watermark']) {
            $problems['fiches_after_watermark'] = sprintf(
                'Sinds %s zijn er %d fiches gepubliceerd die nog geen koppelronde gezien hebben (maximum %d). Draai themes:suggest-fiches, kijk de diff na en draai themes:import.',
                $watermark, $fichesAfterWatermark, (int) $thresholds['max_fiches_after_watermark'],
            );
        }

        $snapshot = [
            'checked_at' => now()->toIso8601String(),
            'horizon_days' => $horizon['days'],
            'horizon_date' => $horizon['date'],
            'empty_upcoming' => $emptyUpcoming,
            'drift' => $drift,
            'watermark' => $watermark,
            'fiches_after_watermark' => $fichesAfterWatermark,
            'exceeded' => array_keys($problems),
        ];

        Cache::forever(self::SNAPSHOT_KEY, $snapshot);

        $this->report($snapshot, $problems);

        $newAlerts = $this->collectNewAlerts($problems, (int) $thresholds['cooldown_days']);

        if ($newAlerts !== []) {
            $this->sendAlert($newAlerts);
            Log::warning('Themakalender: drempel overschreden', ['conditions' => array_keys($newAlerts)]);
        }

        return self::SUCCESS;
    }

    /**
     * Dagen tot de laatste gelegenheid in de databank, gerekend vanaf vandaag.
     *
     * @return array{days: int|null, date: string|null}
     */
    private function horizon(CarbonImmutable $today): array
    {
        $latestStart = ThemeOccurrence::query()->max('start_date');
        $latestEnd = ThemeOccurrence::query()->max('end_date');

        $latest = collect([$latestStart, $latestEnd])
            ->filter()
            ->map(fn (string $date): CarbonImmutable => CarbonImmutable::parse($date)->startOfDay())
            ->max();

        if ($latest === null) {
            return ['days' => null, 'date' => null];
        }

        return [
            'days' => (int) $today->diffInDays($latest, false),
            'date' => $latest->toDateString(),
        ];
    }

    /**
     * Thema's met een gelegenheid in het venster, met een gevulde fiche_slugs
     * in het bestand, waarvan geen enkele slug naar een gepubliceerde fiche
     * wijst. Een lege lijst betekent bewust leeg en telt nooit mee.
     *
     * @param  list<array<string, mixed>>  $themes
     * @return list<string>
     */
    private function emptyUpcomingThemes(array $themes, CarbonImmutable $today, int $windowDays): array
    {
        $windowEnd = $today->addDays($windowDays);

        $upcomingSlugs = ThemeOccurrence::query()
            ->where('start_date', '<=', $windowEnd->toDateString())
            ->where(function (Builder $query) use ($today): void {
                $query->where('end_date', '>=', $today->toDateString())
                    ->orWhere(function (Builder $sameDay) use ($today): void {
                        $sameDay->whereNull('end_date')->where('start_date', '>=', $today->toDateString());
                    });
            })
            ->with('theme')
            ->get()
            ->map(fn (ThemeOccurrence $occurrence): ?string => $occurrence->theme?->slug)
            ->filter()
            ->unique()
            ->values();

        $candidates = collect($themes)
            ->filter(fn (array $theme): bool => $upcomingSlugs->contains($theme['slug'] ?? null))
            ->filter(fn (array $theme): bool => is_array($theme['fiche_slugs'] ?? null) && array_filter($theme['fiche_slugs']) !== [])
            ->values();

        if ($candidates->isEmpty()) {
            return [];
        }

        $publishedSlugs = Fiche::query()
            ->published()
            ->whereIn('slug', $candidates->flatMap(fn (array $theme): array => $theme['fiche_slugs'])->unique()->all())
            ->pluck('slug');

        return $candidates
            ->reject(fn (array $theme): bool => $publishedSlugs->intersect($theme['fiche_slugs'])->isNotEmpty())
            ->map(fn (array $theme): string => $theme['slug'])
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Symmetrisch verschil tussen de oplosbare koppelingen in het bestand en
     * de rijen in fiche_theme. Oplosbaar betekent wat themes:import zou
     * koppelen: een fiche die bestaat en niet in de prullenbak zit.
     *
     * @param  list<array<string, mixed>>  $themes
     * @return array{count: int, summary: string}
     */
    private function drift(array $themes): array
    {
        $prescribed = collect($themes)
            ->filter(fn (array $theme): bool => is_array($theme['fiche_slugs'] ?? null))
            ->flatMap(fn (array $theme): array => array_map(
                fn (string $ficheSlug): string => $theme['slug'].' > '.$ficheSlug,
                array_values(array_filter($theme['fiche_slugs'])),
            ));

        $ficheSlugs = $prescribed->map(fn (string $pair): string => explode(' > ', $pair, 2)[1])->unique();
        $existing = Fiche::query()->whereIn('slug', $ficheSlugs->all())->pluck('slug');

        $filePairs = $prescribed
            ->filter(fn (string $pair): bool => $existing->contains(explode(' > ', $pair, 2)[1]))
            ->unique();

        $databasePairs = Theme::query()
            ->with(['fiches' => fn ($query) => $query->withTrashed()])
            ->get()
            ->flatMap(fn (Theme $theme) => $theme->fiches->map(
                fn (Fiche $fiche): string => $theme->slug.' > '.$fiche->slug,
            ));

        $onlyInFile = $filePairs->diff($databasePairs)->count();
        $onlyInDatabase = $databasePairs->diff($filePairs)->count();

        return [
            'count' => $onlyInFile + $onlyInDatabase,
            'summary' => sprintf(
                '%d koppeling(en) alleen in het bestand, %d alleen in de databank',
                $onlyInFile, $onlyInDatabase,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function watermark(array $data): ?string
    {
        $raw = $data['fiche_match_watermark'] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function fichesAfterWatermark(string $watermark): int
    {
        return Fiche::query()
            ->published()
            ->where('created_at', '>', CarbonImmutable::parse($watermark)->endOfDay())
            ->count();
    }

    /**
     * Voorwaarden die overschreden zijn en niet in hun afkoelperiode zitten.
     * Een voorwaarde die weer gezond is, verliest haar afkoelsleutel meteen.
     *
     * @param  array<string, string>  $problems
     * @return array<string, string>
     */
    private function collectNewAlerts(array $problems, int $cooldownDays): array
    {
        $newAlerts = [];

        foreach (self::CONDITIONS as $condition) {
            $key = self::ALERT_KEY_PREFIX.$condition;

            if (! isset($problems[$condition])) {
                Cache::forget($key);

                continue;
            }

            if (Cache::has($key)) {
                continue;
            }

            $newAlerts[$condition] = $problems[$condition];
            Cache::put($key, true, now()->addDays($cooldownDays));
        }

        return $newAlerts;
    }

    /**
     * @param  Snapshot  $snapshot
     * @param  array<string, string>  $problems
     */
    private function report(array $snapshot, array $problems): void
    {
        $this->components->twoColumnDetail('Horizon', $snapshot['horizon_days'] === null ? 'geen gelegenheden' : "{$snapshot['horizon_days']} dagen ({$snapshot['horizon_date']})");
        $this->components->twoColumnDetail('Aankomende thema\'s zonder gepubliceerde fiches', (string) count($snapshot['empty_upcoming']));
        $this->components->twoColumnDetail('Verschil bestand en databank', (string) $snapshot['drift']['count']);
        $this->components->twoColumnDetail('Gepubliceerde fiches na de datumstempel', $snapshot['fiches_after_watermark'] === null ? 'geen datumstempel' : (string) $snapshot['fiches_after_watermark']);

        if ($problems === []) {
            $this->components->info('Themakalender OK.');

            return;
        }

        foreach ($problems as $message) {
            $this->components->warn($message);
        }
    }

    /**
     * @param  array<string, string>  $alerts
     */
    private function sendAlert(array $alerts): void
    {
        $lines = array_map(fn (string $message): string => '- '.$message, array_values($alerts));

        $body = "De themakalender heeft aandacht nodig.\n\n"
            .implode("\n\n", $lines)
            ."\n\nDe volledige momentopname staat op /admin/gezondheid. Deze melding komt per voorwaarde hoogstens om de "
            .(int) config('themes.health.cooldown_days').' dagen terug zolang ze niet opgelost is.';

        Mail::raw($body, function ($message): void {
            $message->to(config('mail.admin_address') ?: config('mail.from.address'))
                ->subject('Hartverwarmers: de themakalender heeft aandacht nodig');
        });
    }
}
