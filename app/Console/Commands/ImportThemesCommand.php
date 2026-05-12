<?php

namespace App\Console\Commands;

use App\Enums\ThemeRecurrenceRule;
use App\Models\Fiche;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportThemesCommand extends Command
{
    protected $signature = 'themes:import
        {--file=database/seeders/data/themes.json : Path to the import JSON, absolute or relative to base_path}
        {--dry-run : Roll back all changes at the end so nothing is persisted. Useful to preview against production.}';

    protected $description = 'Import or update themes, occurrences, and fiche links from a JSON file. Idempotent.';

    public function handle(): int
    {
        $path = $this->resolvePath($this->option('file'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("Bestand niet gevonden: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || ! isset($data['themes']) || ! is_array($data['themes'])) {
            $this->error('JSON moet een top-level "themes" array bevatten.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY-RUN: alle wijzigingen worden aan het einde teruggedraaid.');
        }

        $stats = [
            'themes_created' => 0,
            'themes_updated' => 0,
            'occurrences_upserted' => 0,
            'fiche_links_synced' => 0,
            'unknown_fiche_slugs' => 0,
        ];

        DB::beginTransaction();
        try {
            (function () use ($data, &$stats) {
                foreach ($data['themes'] as $row) {
                    $rawRule = $row['recurrence_rule'] ?? '(ontbreekt)';
                    $rule = ThemeRecurrenceRule::tryFrom($rawRule);
                    if ($rule === null) {
                        throw new \RuntimeException("Onbekende recurrence_rule: {$rawRule} (thema: {$row['slug']}).");
                    }

                    $theme = Theme::where('slug', $row['slug'])->first();
                    $attributes = [
                        'title' => $row['title'],
                        'description' => $row['description'] ?? null,
                        'is_month' => (bool) ($row['is_month'] ?? false),
                        'recurrence_rule' => $rule->value,
                        'recurrence_detail' => $row['recurrence_detail'] ?? null,
                    ];

                    if ($theme) {
                        $theme->update($attributes);
                        $stats['themes_updated']++;
                    } else {
                        Theme::create(array_merge(['slug' => $row['slug']], $attributes));
                        $stats['themes_created']++;
                    }
                }

                foreach ($data['themes'] as $row) {
                    if (! array_key_exists('fiche_slugs', $row)) {
                        continue;
                    }

                    $theme = Theme::where('slug', $row['slug'])->first();
                    $slugs = is_array($row['fiche_slugs']) ? array_values(array_filter($row['fiche_slugs'])) : [];

                    if ($slugs === []) {
                        $theme->fiches()->sync([]);

                        continue;
                    }

                    $found = Fiche::whereIn('slug', $slugs)->pluck('id', 'slug');

                    foreach ($slugs as $slug) {
                        if (! $found->has($slug)) {
                            $this->warn("Onbekende fiche-slug bij thema {$row['slug']}: {$slug}");
                            $stats['unknown_fiche_slugs']++;
                        }
                    }

                    $theme->fiches()->sync($found->values()->all());
                    $stats['fiche_links_synced'] += $found->count();
                }

                foreach ($data as $key => $rows) {
                    if (! str_starts_with($key, 'occurrences_') || ! is_array($rows)) {
                        continue;
                    }

                    foreach ($rows as $row) {
                        $theme = Theme::where('slug', $row['theme_slug'])->first();
                        if (! $theme) {
                            $this->warn("Occurrence verwijst naar onbekende thema-slug: {$row['theme_slug']}");

                            continue;
                        }

                        ThemeOccurrence::updateOrCreate(
                            ['theme_id' => $theme->id, 'year' => (int) $row['year']],
                            ['start_date' => $row['start_date'], 'end_date' => $row['end_date'] ?? null],
                        );
                        $stats['occurrences_upserted']++;
                    }
                }
            })();

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\RuntimeException $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $dryRun) {
            Cache::forget('home:upcoming-themes:'.today()->toDateString());
        }

        $this->newLine();
        $this->info(sprintf(
            "%sThema's: %d aangemaakt, %d bijgewerkt. Occurrences: %d. Fiche-links: %d gekoppeld, %d onbekende slugs.",
            $dryRun ? '[dry-run] ' : 'Klaar. ',
            $stats['themes_created'], $stats['themes_updated'], $stats['occurrences_upserted'],
            $stats['fiche_links_synced'], $stats['unknown_fiche_slugs'],
        ));

        return self::SUCCESS;
    }

    private function resolvePath(string $option): string
    {
        return str_starts_with($option, '/') ? $option : base_path($option);
    }
}
