<?php

namespace Tests\Feature\Infrastructure;

use App\Models\Fiche;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ThemesHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    private const SNAPSHOT_KEY = 'themes:health-check:snapshot';

    private string $fixturePath = '';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-07 09:00:00');
        Cache::flush();
        config(['mail.admin_address' => 'beheer@example.test']);

        $this->fixturePath = tempnam(sys_get_temp_dir(), 'themes-health-');
        config(['themes.file' => $this->fixturePath]);
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturePath);

        parent::tearDown();
    }

    public function test_sends_nothing_when_the_calendar_is_healthy(): void
    {
        Mail::spy();
        $this->writeThemesFile($this->seedHealthyDatabase());

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldNotHaveReceived('raw');

        $snapshot = Cache::get(self::SNAPSHOT_KEY);
        $this->assertSame(now()->toIso8601String(), $snapshot['checked_at']);
        $this->assertSame(200, $snapshot['horizon_days']);
        $this->assertSame([], $snapshot['empty_upcoming']);
        $this->assertSame(0, $snapshot['drift']['count']);
        $this->assertSame(0, $snapshot['fiches_after_watermark']);
        $this->assertSame([], $snapshot['exceeded']);
    }

    public function test_sends_exactly_one_mail_when_the_horizon_drops_below_sixty_days(): void
    {
        Mail::spy();
        $this->writeThemesFile($this->seedHealthyDatabase(horizonDaysAhead: 100));

        $this->artisan('themes:health-check')->assertSuccessful();
        Mail::shouldNotHaveReceived('raw');

        $this->travel(41)->days();
        $this->artisan('themes:health-check')->assertSuccessful();
        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();
        $this->assertTrue(Cache::has('themes:health-check:alerted:horizon'));
        $this->assertSame(['horizon'], Cache::get(self::SNAPSHOT_KEY)['exceeded']);
    }

    public function test_stays_silent_for_fourteen_days_and_then_reminds(): void
    {
        Mail::spy();
        $this->writeThemesFile($this->seedHealthyDatabase(horizonDaysAhead: 50));

        $this->artisan('themes:health-check')->assertSuccessful();
        $this->travel(7)->days();
        $this->artisan('themes:health-check')->assertSuccessful();
        $this->travel(6)->days();
        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();

        $this->travel(2)->days();
        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->twice();
    }

    public function test_forgets_the_cooldown_once_the_condition_is_healthy_again(): void
    {
        Mail::spy();
        $rows = $this->seedHealthyDatabase(horizonDaysAhead: 50);
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();
        $this->assertTrue(Cache::has('themes:health-check:alerted:horizon'));

        $this->createTheme('nieuwjaar', today()->addDays(300)->toDateString(), ['nieuwjaarsreceptie']);
        $rows[] = ['slug' => 'nieuwjaar', 'fiche_slugs' => ['nieuwjaarsreceptie']];
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();

        $this->assertFalse(Cache::has('themes:health-check:alerted:horizon'));
        Mail::shouldHaveReceived('raw')->once();
    }

    public function test_a_theme_with_an_empty_slug_list_never_counts_as_broken(): void
    {
        Mail::spy();
        $rows = $this->seedHealthyDatabase();
        $this->createTheme('baarddag', today()->addDays(10)->toDateString());
        $rows[] = ['slug' => 'baarddag', 'fiche_slugs' => []];
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldNotHaveReceived('raw');
        $this->assertSame([], Cache::get(self::SNAPSHOT_KEY)['empty_upcoming']);
    }

    public function test_flags_an_upcoming_theme_whose_links_have_rotted(): void
    {
        Mail::spy();
        $rows = $this->seedHealthyDatabase();
        $theme = $this->createTheme('dag-van-de-verzorgenden', today()->addDays(10)->toDateString());
        $unpublished = Fiche::factory()->create(['slug' => 'massagestoel', 'created_at' => '2026-08-01 10:00:00']);
        $theme->fiches()->attach($unpublished);
        $rows[] = ['slug' => 'dag-van-de-verzorgenden', 'fiche_slugs' => ['massagestoel']];
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();
        $snapshot = Cache::get(self::SNAPSHOT_KEY);
        $this->assertSame(['dag-van-de-verzorgenden'], $snapshot['empty_upcoming']);
        $this->assertSame(['empty_upcoming'], $snapshot['exceeded']);
    }

    public function test_ignores_a_rotted_theme_outside_the_upcoming_window(): void
    {
        Mail::spy();
        $rows = $this->seedHealthyDatabase();
        $theme = $this->createTheme('wereld-dovendag', today()->addDays(90)->toDateString());
        $unpublished = Fiche::factory()->create(['slug' => 'gebarentaal', 'created_at' => '2026-08-01 10:00:00']);
        $theme->fiches()->attach($unpublished);
        $rows[] = ['slug' => 'wereld-dovendag', 'fiche_slugs' => ['gebarentaal']];
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldNotHaveReceived('raw');
    }

    public function test_flags_drift_between_the_file_and_the_database(): void
    {
        Mail::spy();
        $rows = $this->seedHealthyDatabase();
        Fiche::factory()->published()->create(['slug' => 'kerstmarkt', 'created_at' => '2026-08-01 10:00:00']);
        $rows[1]['fiche_slugs'][] = 'kerstmarkt';
        $rows[] = ['slug' => 'onbekend-thema', 'fiche_slugs' => ['bestaat-niet']];
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();
        $snapshot = Cache::get(self::SNAPSHOT_KEY);
        $this->assertSame(1, $snapshot['drift']['count']);
        $this->assertSame(['drift'], $snapshot['exceeded']);
    }

    public function test_counts_a_database_row_the_file_no_longer_prescribes_as_drift(): void
    {
        Mail::spy();
        $rows = $this->seedHealthyDatabase();
        $rows[1]['fiche_slugs'] = [];
        $this->writeThemesFile($rows);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();
        $this->assertSame(1, Cache::get(self::SNAPSHOT_KEY)['drift']['count']);
    }

    public function test_treats_a_missing_watermark_as_exceeded(): void
    {
        Mail::spy();
        $this->writeThemesFile($this->seedHealthyDatabase(), watermark: null);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();
        $snapshot = Cache::get(self::SNAPSHOT_KEY);
        $this->assertNull($snapshot['watermark']);
        $this->assertNull($snapshot['fiches_after_watermark']);
        $this->assertSame(['fiches_after_watermark'], $snapshot['exceeded']);
    }

    public function test_counts_published_fiches_created_after_the_watermark(): void
    {
        Mail::spy();
        config(['themes.health.max_fiches_after_watermark' => 2]);
        $this->writeThemesFile($this->seedHealthyDatabase());

        Fiche::factory()->published()->count(3)->create(['created_at' => '2026-09-03 10:00:00']);
        Fiche::factory()->published()->create(['created_at' => '2026-09-01 15:00:00']);
        Fiche::factory()->create(['created_at' => '2026-09-04 10:00:00']);

        $this->artisan('themes:health-check')->assertSuccessful();

        Mail::shouldHaveReceived('raw')->once();
        $snapshot = Cache::get(self::SNAPSHOT_KEY);
        $this->assertSame(3, $snapshot['fiches_after_watermark']);
        $this->assertSame(['fiches_after_watermark'], $snapshot['exceeded']);
    }

    public function test_fails_when_the_file_is_missing(): void
    {
        Mail::spy();
        config(['themes.file' => '/nonexistent/themes.json']);

        $this->artisan('themes:health-check')->assertFailed();

        Mail::shouldNotHaveReceived('raw');
    }

    /**
     * A healthy calendar: a theme in three weeks with a published fiche and a
     * theme at the horizon with one too, both linked in the database exactly
     * as the file prescribes. Returns the file rows so a test can bend them.
     *
     * @return list<array{slug: string, fiche_slugs: list<string>}>
     */
    private function seedHealthyDatabase(int $horizonDaysAhead = 200): array
    {
        $this->createTheme('wereldyogadag', today()->addDays(20)->toDateString(), ['yoga-voor-bewoners']);
        $this->createTheme('kerst', today()->addDays($horizonDaysAhead)->toDateString(), ['kerstkoor']);

        return [
            ['slug' => 'wereldyogadag', 'fiche_slugs' => ['yoga-voor-bewoners']],
            ['slug' => 'kerst', 'fiche_slugs' => ['kerstkoor']],
        ];
    }

    /**
     * @param  list<string>  $publishedFicheSlugs
     */
    private function createTheme(string $slug, string $occursOn, array $publishedFicheSlugs = []): Theme
    {
        $theme = Theme::factory()->create(['slug' => $slug, 'title' => ucfirst($slug)]);

        ThemeOccurrence::factory()->for($theme)->create([
            'year' => (int) substr($occursOn, 0, 4),
            'start_date' => $occursOn,
        ]);

        foreach ($publishedFicheSlugs as $ficheSlug) {
            $fiche = Fiche::factory()->published()->create([
                'slug' => $ficheSlug,
                'created_at' => '2026-08-01 10:00:00',
            ]);
            $theme->fiches()->attach($fiche);
        }

        return $theme;
    }

    /**
     * @param  list<array{slug: string, fiche_slugs?: list<string>}>  $themes
     */
    private function writeThemesFile(array $themes, ?string $watermark = '2026-09-01'): void
    {
        $data = [];

        if ($watermark !== null) {
            $data['fiche_match_watermark'] = $watermark;
        }

        $data['themes'] = array_map(fn (array $theme): array => array_merge([
            'title' => ucfirst($theme['slug']),
            'description' => null,
            'is_month' => false,
            'recurrence_rule' => 'fixed',
            'recurrence_detail' => 'Fixed: month-day 06-21',
        ], $theme), $themes);

        file_put_contents(
            $this->fixturePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }
}
