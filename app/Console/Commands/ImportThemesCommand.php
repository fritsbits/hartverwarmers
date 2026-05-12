<?php

namespace App\Console\Commands;

use App\Enums\ThemeRecurrenceRule;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportThemesCommand extends Command
{
    protected $signature = 'themes:import {--file=database/seeders/data/themes.json : Path to the import JSON, absolute or relative to base_path}';

    protected $description = 'Import or update themes, occurrences, and fiche links from a JSON file. Idempotent.';

    public function handle(): int
    {
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

        $stats = ['themes_created' => 0, 'themes_updated' => 0, 'occurrences_upserted' => 0];

        try {
            DB::transaction(function () use ($data, &$stats) {
                foreach ($data['themes'] as $row) {
                    $rule = ThemeRecurrenceRule::tryFrom($row['recurrence_rule'] ?? '');
                    if ($rule === null) {
                        throw new \RuntimeException("Onbekende recurrence_rule: {$row['recurrence_rule']} (thema: {$row['slug']}).");
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
            });
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Klaar. Thema\'s: %d aangemaakt, %d bijgewerkt. Occurrences: %d.',
            $stats['themes_created'], $stats['themes_updated'], $stats['occurrences_upserted'],
        ));

        return self::SUCCESS;
    }

    private function resolvePath(string $option): string
    {
        return str_starts_with($option, '/') ? $option : base_path($option);
    }
}
