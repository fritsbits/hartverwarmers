<?php

namespace App\Console\Commands;

use App\Enums\ThemeRecurrenceRule;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ExtendThemeOccurrencesCommand extends Command
{
    protected $signature = 'themes:extend-occurrences
        {--year= : Doeljaar waarvoor occurrences_JJJJ berekend wordt}
        {--file=database/seeders/data/themes.json : Path to the themes JSON, absolute or relative to base_path}
        {--force : Overschrijf een bestaand occurrences_JJJJ-blok}
        {--dry-run : Toon de berekende datums zonder het bestand te schrijven}';

    protected $description = 'Bereken de occurrences van een doeljaar uit de recurrence_rule per thema en schrijf ze als occurrences_JJJJ in de themes JSON. Raakt de databank nooit aan; draai daarna themes:import.';

    /**
     * Regels die geen rekenwerk toelaten: die thema's worden overgeslagen en aan
     * het eind als handwerk gemeld, met de datums van de referentie als houvast.
     *
     * @var list<ThemeRecurrenceRule>
     */
    private const array MANUAL_RULES = [
        ThemeRecurrenceRule::VariableAnnual,
        ThemeRecurrenceRule::SchoolCalendar,
        ThemeRecurrenceRule::Lunar,
        ThemeRecurrenceRule::OneTimeEvent,
    ];

    public function handle(): int
    {
        $year = (int) $this->option('year');

        if ($year < 2000 || $year > 2200) {
            $this->error('Geef een doeljaar op met --year=JJJJ.');

            return self::FAILURE;
        }

        $path = $this->resolvePath($this->option('file'));

        if (! is_file($path)) {
            $this->error("Bestand niet gevonden: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);

        if (! is_array($data) || ! isset($data['themes']) || ! is_array($data['themes'])) {
            $this->error('JSON moet een top-level "themes" array bevatten.');

            return self::FAILURE;
        }

        $key = "occurrences_{$year}";

        if (array_key_exists($key, $data) && ! $this->option('force')) {
            $this->error("{$key} bestaat al in {$path}. Gebruik --force om het blok te overschrijven.");

            return self::FAILURE;
        }

        $references = $this->references($data, $year);

        $rows = [];
        $manual = [];
        $withoutReference = [];

        try {
            foreach ($data['themes'] as $theme) {
                $slug = $theme['slug'];
                $rule = ThemeRecurrenceRule::tryFrom($theme['recurrence_rule'] ?? '');

                if ($rule === null) {
                    throw new \RuntimeException("Onbekende recurrence_rule bij thema {$slug}: ".($theme['recurrence_rule'] ?? '(ontbreekt)'));
                }

                $reference = $references[$slug] ?? null;

                if ($reference === null) {
                    $withoutReference[] = $slug;

                    continue;
                }

                if (in_array($rule, self::MANUAL_RULES, true)) {
                    $manual[] = [$slug, $rule->value, $reference['start_date'], $reference['end_date'] ?? '-'];

                    continue;
                }

                $start = $this->startDate($rule, $theme, $reference, $year);
                $end = $this->endDate($start, $reference);

                $rows[] = [
                    'theme_slug' => $slug,
                    'year' => $year,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end?->toDateString(),
                ];
            }
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        usort($rows, fn (array $a, array $b): int => strcmp($a['start_date'], $b['start_date']));

        $this->report($year, $rows, $manual, $withoutReference);

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: het bestand is niet aangepast.');

            return self::SUCCESS;
        }

        $data[$key] = $rows;

        file_put_contents($path, $this->encode($data));

        $this->info('Geschreven naar '.$path.'. Kijk de diff na, vul het handwerk in en draai daarna themes:import.');

        return self::SUCCESS;
    }

    /**
     * De meest recente occurrence per thema uit alle occurrences_*-blokken vóór
     * het doeljaar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{theme_slug: string, year: int, start_date: string, end_date?: string|null}>
     */
    private function references(array $data, int $year): array
    {
        $references = [];

        foreach ($data as $key => $rows) {
            if (! preg_match('/^occurrences_(\d{4})$/', $key, $match) || ! is_array($rows)) {
                continue;
            }

            $blockYear = (int) $match[1];

            if ($blockYear >= $year) {
                continue;
            }

            foreach ($rows as $row) {
                $slug = $row['theme_slug'];

                if (! isset($references[$slug]) || $references[$slug]['year'] < $blockYear) {
                    $references[$slug] = array_merge($row, ['year' => $blockYear]);
                }
            }
        }

        return $references;
    }

    /**
     * @param  array<string, mixed>  $theme
     * @param  array{start_date: string, end_date?: string|null}  $reference
     */
    private function startDate(ThemeRecurrenceRule $rule, array $theme, array $reference, int $year): CarbonImmutable
    {
        $detail = (string) ($theme['recurrence_detail'] ?? '');

        return match ($rule) {
            ThemeRecurrenceRule::Fixed => $this->fixedDate($theme['slug'], $reference['start_date'], $year),
            ThemeRecurrenceRule::NthWeekday => $this->nthWeekdayDate($theme['slug'], $detail, $year),
            ThemeRecurrenceRule::Easter => $this->easterDate($theme['slug'], $detail, $year),
            default => throw new \RuntimeException("Geen berekening voor regel {$rule->value} bij thema {$theme['slug']}."),
        };
    }

    private function fixedDate(string $slug, string $referenceStart, int $year): CarbonImmutable
    {
        $reference = CarbonImmutable::parse($referenceStart);

        if ($reference->month === 2 && $reference->day === 29 && ! CarbonImmutable::create($year)->isLeapYear()) {
            throw new \RuntimeException("Thema {$slug} valt op 29 februari en {$year} is geen schrikkeljaar.");
        }

        return CarbonImmutable::create($year, $reference->month, $reference->day)->startOfDay();
    }

    /**
     * Alleen "2nd Sunday of June" en "Last Sunday of October" komen voor; elke
     * andere formulering is een fout, geen gok.
     */
    private function nthWeekdayDate(string $slug, string $detail, int $year): CarbonImmutable
    {
        $pattern = '/^(?<ordinal>[1-4](?:st|nd|rd|th)|Last) (?<weekday>Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday) of (?<month>January|February|March|April|May|June|July|August|September|October|November|December)$/';

        if (! preg_match($pattern, trim($detail), $match)) {
            throw new \RuntimeException("Onontleedbare recurrence_detail bij thema {$slug}: \"{$detail}\". Verwacht \"2nd Sunday of June\" of \"Last Sunday of October\".");
        }

        $ordinal = match ($match['ordinal']) {
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third',
            '4th' => 'fourth',
            default => 'last',
        };

        return CarbonImmutable::parse("{$ordinal} {$match['weekday']} of {$match['month']} {$year}")->startOfDay();
    }

    private function easterDate(string $slug, string $detail, int $year): CarbonImmutable
    {
        if (! preg_match('/^Easter (?<sign>[+-]) ?(?<days>\d+) days?\b/', trim($detail), $match)) {
            throw new \RuntimeException("Onontleedbare recurrence_detail bij thema {$slug}: \"{$detail}\". Verwacht \"Easter + 49 days\".");
        }

        $offset = (int) $match['days'] * ($match['sign'] === '-' ? -1 : 1);

        return $this->easterSunday($year)->addDays($offset);
    }

    /**
     * Anoniem Gregoriaans algoritme (Meeus/Jones/Butcher).
     */
    private function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    /**
     * Behoud de duur in dagen, zodat een gelegenheid met end_date evenveel
     * dagen blijft beslaan.
     *
     * @param  array{start_date: string, end_date?: string|null}  $reference
     */
    private function endDate(CarbonImmutable $start, array $reference): ?CarbonImmutable
    {
        if (empty($reference['end_date'])) {
            return null;
        }

        $days = CarbonImmutable::parse($reference['start_date'])->diffInDays(CarbonImmutable::parse($reference['end_date']));

        return $start->addDays((int) $days);
    }

    /**
     * @param  list<array{theme_slug: string, year: int, start_date: string, end_date: string|null}>  $rows
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $manual
     * @param  list<string>  $withoutReference
     */
    private function report(int $year, array $rows, array $manual, array $withoutReference): void
    {
        if ($rows !== []) {
            $this->table(
                ['Thema', 'Start', 'Einde'],
                array_map(fn (array $row): array => [$row['theme_slug'], $row['start_date'], $row['end_date'] ?? '-'], $rows),
            );
        }

        $this->info(count($rows)." occurrences berekend voor {$year}.");

        if ($manual !== []) {
            $this->newLine();
            $this->warn('Handwerk ('.count($manual).'): deze thema\'s zijn niet berekenbaar en ontbreken in het blok. Vul ze zelf in; ter houvast de datums van de referentie.');

            foreach ($manual as [$slug, $rule, $start, $end]) {
                $this->line("  {$slug} ({$rule}): {$start} tot {$end}");
            }
        }

        if ($withoutReference !== []) {
            $this->newLine();
            $this->warn('Zonder referentie ('.count($withoutReference).'): geen eerdere occurrence gevonden, overgeslagen: '.implode(', ', $withoutReference));
        }
    }

    /**
     * Dezelfde encoder als themes:suggest-fiches: twee spaties inspringing,
     * zodat de diff alleen het nieuwe blok toont.
     *
     * @param  array<string, mixed>  $data
     */
    private function encode(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return preg_replace_callback(
            '/^ +/m',
            fn (array $match): string => str_repeat(' ', intdiv(strlen($match[0]), 2)),
            $json
        )."\n";
    }

    private function resolvePath(string $option): string
    {
        return str_starts_with($option, '/') ? $option : base_path($option);
    }
}
