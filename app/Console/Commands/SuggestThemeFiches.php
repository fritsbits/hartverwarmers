<?php

namespace App\Console\Commands;

use App\Ai\Agents\ThemeFicheMatchAgent;
use App\Models\Fiche;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SuggestThemeFiches extends Command
{
    protected $signature = 'themes:suggest-fiches
        {--file=database/seeders/data/themes.json : Path to the themes JSON, absolute or relative to base_path}
        {--slug=* : Only run for these theme slugs}
        {--max=6 : Maximum number of fiches to link per theme}
        {--force : Also redo themes that already have fiche_slugs}
        {--dry-run : Report what would change without writing the file}';

    protected $description = 'Suggest fiche links per calendar theme and write them into the themes JSON as fiche_slugs. Never writes to the database; run themes:import afterwards.';

    /**
     * A theme keeps whatever links it already has unless the run finds at least
     * one match, so a bad AI day can never empty a hand-curated list.
     *
     * A run over the whole calendar also moves fiche_match_watermark to today:
     * themes:health-check counts the fiches published after that date as
     * "not yet matched". A run limited with --slug leaves the stamp alone,
     * because the new fiches were only held against some of the themes.
     */
    public function handle(): int
    {
        if (empty(config('ai.providers.anthropic.key'))) {
            $this->error('Geen Anthropic-sleutel geconfigureerd.');

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

        $catalogue = $this->catalogue();

        if ($catalogue->isEmpty()) {
            $this->warn('Geen gepubliceerde fiches gevonden, er valt niets te koppelen.');

            return self::SUCCESS;
        }

        $catalogueText = $catalogue->map(fn (array $fiche): string => $this->catalogueLine($fiche))->implode("\n");
        $max = max(1, (int) $this->option('max'));
        $only = (array) $this->option('slug');
        $force = (bool) $this->option('force');

        $rows = [];
        $empty = [];
        $failed = [];

        foreach ($data['themes'] as $index => $theme) {
            if ($only !== [] && ! in_array($theme['slug'] ?? '', $only, true)) {
                continue;
            }

            if (! $force && array_key_exists('fiche_slugs', $theme)) {
                continue;
            }

            $this->line("Bezig met {$theme['slug']} ...");

            $matches = $this->suggestFor($theme, $catalogueText);

            if ($matches === null) {
                $failed[] = $theme['slug'];

                continue;
            }

            $slugs = $this->validate($matches, $catalogue, $theme['slug']);

            if ($slugs === []) {
                $empty[] = $theme['slug'];

                continue;
            }

            $slugs = array_slice($slugs, 0, $max);
            $data['themes'][$index]['fiche_slugs'] = $slugs;

            $rows[] = [$theme['slug'], count($slugs), implode(', ', $slugs)];
        }

        $this->report($rows, $empty, $failed);

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: het bestand is niet aangepast.');

            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->info('Niets gewijzigd.');

            return self::SUCCESS;
        }

        if ($only === []) {
            $data = $this->stampWatermark($data);
        }

        file_put_contents($path, $this->encode($data));

        $this->info('Geschreven naar '.$path.'. Kijk de diff na en draai daarna themes:import.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<string, array{slug: string, title: string, description: string, initiative: string}>
     */
    private function catalogue(): Collection
    {
        return Fiche::query()
            ->published()
            ->with('initiative:id,title')
            ->orderBy('title')
            ->get(['id', 'initiative_id', 'slug', 'title', 'description'])
            ->map(fn (Fiche $fiche): array => [
                'slug' => $fiche->slug,
                'title' => $fiche->title,
                'description' => Str::limit(trim(strip_tags((string) $fiche->description)), 160),
                'initiative' => (string) $fiche->initiative?->title,
            ])
            ->keyBy('slug');
    }

    /**
     * @param  array{slug: string, title: string, description: string, initiative: string}  $fiche
     */
    private function catalogueLine(array $fiche): string
    {
        return trim(implode(' | ', array_filter([
            $fiche['slug'],
            $fiche['title'],
            $fiche['initiative'],
            $fiche['description'],
        ])));
    }

    /**
     * @param  array<string, mixed>  $theme
     * @return list<array<string, mixed>>|null Null when the AI call itself failed.
     */
    private function suggestFor(array $theme, string $catalogueText): ?array
    {
        $prompt = "Thema: {$theme['title']}\n";

        if (! empty($theme['description'])) {
            $prompt .= "Beschrijving van het thema: {$theme['description']}\n";
        }

        $prompt .= "\nCatalogus van fiches (slug | titel | initiatief | beschrijving):\n{$catalogueText}";

        try {
            $response = (new ThemeFicheMatchAgent)->prompt($prompt);

            return array_values((array) ($response['matches'] ?? []));
        } catch (\Throwable $e) {
            $this->warn("Mislukt voor {$theme['slug']}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Keep only slugs that exist in the catalogue. A model that invents a slug
     * would otherwise land a warning in themes:import much later, far from here.
     *
     * @param  list<array<string, mixed>>  $matches
     * @param  Collection<string, array<string, mixed>>  $catalogue
     * @return list<string>
     */
    private function validate(array $matches, Collection $catalogue, string $themeSlug): array
    {
        $slugs = [];

        foreach ($matches as $match) {
            $slug = is_array($match) ? ($match['slug'] ?? null) : $match;

            if (! is_string($slug) || $slug === '') {
                continue;
            }

            if (! $catalogue->has($slug)) {
                $this->warn("Onbekende fiche-slug bij thema {$themeSlug}: {$slug}");

                continue;
            }

            $slugs[$slug] = true;
        }

        return array_keys($slugs);
    }

    /**
     * @param  list<array{0: string, 1: int, 2: string}>  $rows
     * @param  list<string>  $empty
     * @param  list<string>  $failed
     */
    private function report(array $rows, array $empty, array $failed): void
    {
        if ($rows !== []) {
            $this->newLine();
            $this->table(['Thema', 'Aantal', 'Fiches'], $rows);
        }

        if ($empty !== []) {
            $this->newLine();
            $this->line('Geen enkele passende fiche ('.count($empty).'): '.implode(', ', $empty));
        }

        if ($failed !== []) {
            $this->newLine();
            $this->warn('Mislukt ('.count($failed).'): '.implode(', ', $failed));
        }
    }

    /**
     * Keep the stamp as the first key so it stays visible at the top of the
     * file, whether it was already there or is added for the first time.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stampWatermark(array $data): array
    {
        $today = now()->toDateString();

        if (array_key_exists('fiche_match_watermark', $data)) {
            $data['fiche_match_watermark'] = $today;

            return $data;
        }

        return ['fiche_match_watermark' => $today] + $data;
    }

    /**
     * Match the file's own two-space indentation so the diff shows only the
     * added fiche_slugs blocks instead of reindenting all 1500 lines.
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
