<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Support\ThemeCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * De tegenhanger van themes:import, die enkel aanmaakt en bijwerkt.
 *
 * Een thema uit themes.json halen laat zijn rij in de databank staan. Dit
 * commando ruimt die achterblijvers op. Het staat er bewust apart van de
 * import: verwijderen mag nooit meeliften op een deploy, en de deployer mag
 * er nooit per ongeluk in terechtkomen.
 *
 * Zonder --force verwijdert het niets. Dat maakt de onveilige uitkomst de
 * uitkomst die je expliciet moet vragen, ook in Forge's commandopaneel, waar
 * niemand een vraag kan beantwoorden.
 */
class PruneRemovedThemesCommand extends Command
{
    protected $signature = 'themes:prune-removed
        {--file=database/seeders/data/themes.json : Path to the import JSON, absolute or relative to base_path}
        {--force : Werkelijk verwijderen. Zonder deze vlag toont het commando enkel wat er zou verdwijnen.}
        {--max=20 : Weiger te verwijderen als er meer thema\'s zouden sneuvelen dan dit. Vangt een half of kapot JSON-bestand af.}';

    protected $description = 'Verwijder thema\'s die niet meer in de JSON staan. Toont standaard enkel een voorvertoning.';

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

        $slugs = array_values(array_filter(array_column($data['themes'], 'slug')));
        if ($slugs === []) {
            $this->error('De JSON bevat geen enkel thema met een slug. Dat wist de hele kalender, dus er gebeurt niets.');

            return self::FAILURE;
        }

        $doomed = Theme::query()
            ->whereNotIn('slug', $slugs)
            ->withCount(['occurrences', 'fiches'])
            ->orderBy('slug')
            ->get();

        if ($doomed->isEmpty()) {
            $this->info('Niets te verwijderen: elk thema in de databank staat ook in de JSON.');

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Titel', 'Datums', 'Fiches'],
            $doomed->map(fn (Theme $theme): array => [
                $theme->slug,
                $theme->title,
                $theme->occurrences_count,
                $theme->fiches_count,
            ])->all(),
        );

        $ficheLinks = $doomed->sum('fiches_count');
        $this->line(sprintf(
            "%d thema's, %d datums en %d fichekoppelingen. De fiches zelf blijven bestaan.",
            $doomed->count(),
            $doomed->sum('occurrences_count'),
            $ficheLinks,
        ));

        $max = (int) $this->option('max');
        if ($doomed->count() > $max) {
            $this->newLine();
            $this->error(sprintf(
                "Gestopt: %d thema's is meer dan de limiet van %d. Kijk na of de JSON volledig is; verhoog daarna bewust --max.",
                $doomed->count(),
                $max,
            ));

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->warn('Voorvertoning: er is niets verwijderd. Draai opnieuw met --force om het echt te doen.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($doomed): void {
            Theme::whereIn('id', $doomed->pluck('id'))->delete();
        });

        ThemeCache::flush();

        $this->newLine();
        $this->info(sprintf("Klaar. %d thema's verwijderd.", $doomed->count()));

        return self::SUCCESS;
    }

    private function resolvePath(string $option): string
    {
        return str_starts_with($option, '/') ? $option : base_path($option);
    }
}
