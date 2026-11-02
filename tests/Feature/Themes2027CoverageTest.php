<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Theme;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Importeert het echte database/seeders/data/themes.json en kijkt na wat een
 * animator in 2027 te zien krijgt: kalender, printversie en maandmail rond de
 * jaargrens. Elke slug uit het bestand krijgt een gepubliceerde fiche, zodat de
 * import slaagt zonder --allow-unknown-slugs.
 */
class Themes2027CoverageTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = json_decode(file_get_contents(base_path('database/seeders/data/themes.json')), true);

        $slugs = collect($this->data['themes'])->pluck('fiche_slugs')->flatten()->unique();
        foreach ($slugs as $slug) {
            Fiche::factory()->published()->create(['slug' => $slug]);
        }

        $this->artisan('themes:import')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function maandProvider(): array
    {
        return [
            'januari' => ['2027-01'],
            'februari' => ['2027-02'],
            'maart' => ['2027-03'],
            'april' => ['2027-04'],
            'oktober, de drukste maand' => ['2027-10'],
            'december' => ['2027-12'],
        ];
    }

    #[DataProvider('maandProvider')]
    public function test_kalender_en_print_tonen_elk_thema_van_de_maand(string $maand): void
    {
        $titels = $this->titelsDieOverlappen($maand);

        $this->assertNotEmpty($titels, "Geen thema's in {$maand}.");

        foreach (['themes.index', 'themes.print'] as $route) {
            $response = $this->get(route($route, ['maand' => $maand]))->assertOk();

            foreach ($titels as $titel) {
                $response->assertSee($titel, false);
            }
        }
    }

    public function test_maandoverschrijdend_thema_staat_in_beide_maanden(): void
    {
        $rij = collect($this->data['occurrences_2027'])->first(
            fn (array $rij): bool => $rij['end_date'] !== null && substr($rij['start_date'], 0, 7) !== substr($rij['end_date'], 0, 7)
        );

        $this->assertNotNull($rij, 'Geen maandoverschrijdend thema in 2027.');

        $titel = Theme::where('slug', $rij['theme_slug'])->value('title');

        foreach ([substr($rij['start_date'], 0, 7), substr($rij['end_date'], 0, 7)] as $maand) {
            $this->get(route('themes.index', ['maand' => $maand]))->assertOk()->assertSee($titel, false);
            $this->get(route('themes.print', ['maand' => $maand]))->assertOk()->assertSee($titel, false);
        }
    }

    public function test_maandmail_kijkt_over_de_jaargrens_en_toont_alleen_thema_s_met_fiches(): void
    {
        Carbon::setTestNow('2026-12-20 08:00:00');

        $payload = app(Composer::class)->compose(now());

        $this->assertTrue(
            $payload->themes->contains(fn ($occurrence) => $occurrence->year === 2027),
            'De maandmail van 20 december 2026 toont geen enkel thema uit januari 2027.'
        );

        $this->assertTrue(
            $payload->themes->every(fn ($occurrence) => $occurrence->theme->fiches_count > 0),
            'De maandmail toont een thema zonder gepubliceerde fiches.'
        );
    }

    public function test_maandmail_van_begin_januari_2027_is_niet_leeg(): void
    {
        Carbon::setTestNow('2027-01-03 08:00:00');

        $payload = app(Composer::class)->compose(now());

        $this->assertGreaterThan(0, $payload->upcomingThemeCount);
    }

    /**
     * @return list<string>
     */
    private function titelsDieOverlappen(string $maand): array
    {
        $titels = collect($this->data['themes'])->pluck('title', 'slug');

        return collect($this->data['occurrences_2027'])
            ->filter(fn (array $rij): bool => $rij['start_date'] <= "{$maand}-31" && ($rij['end_date'] ?? $rij['start_date']) >= "{$maand}-01")
            ->map(fn (array $rij): string => e($titels[$rij['theme_slug']]))
            ->values()
            ->all();
    }
}
