<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportThemesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturePath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturePath = base_path('tests/fixtures/themes/sample-import.json');
    }

    /**
     * Create every fiche the sample fixture links to, so a strict import succeeds.
     */
    private function createFixtureFiches(): void
    {
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);
        Fiche::factory()->create(['slug' => 'stoel-yoga-quiz']);
    }

    public function test_imports_themes_and_occurrences(): void
    {
        $this->createFixtureFiches();

        $this->artisan('themes:import', ['--file' => $this->fixturePath])
            ->assertExitCode(0);

        $this->assertDatabaseCount('themes', 2);
        $this->assertDatabaseHas('themes', ['slug' => 'wereldyogadag', 'is_month' => false]);
        $this->assertDatabaseHas('themes', ['slug' => 'zomer', 'is_month' => true]);
        $this->assertDatabaseCount('theme_occurrences', 2);
        $this->assertDatabaseHas('theme_occurrences', [
            'year' => 2026,
            'start_date' => '2026-06-21',
            'end_date' => null,
        ]);
    }

    public function test_is_idempotent(): void
    {
        $this->createFixtureFiches();

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);
        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);

        $this->assertDatabaseCount('themes', 2);
        $this->assertDatabaseCount('theme_occurrences', 2);
    }

    public function test_updates_changed_theme_fields_on_reimport(): void
    {
        $this->createFixtureFiches();

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);

        Theme::where('slug', 'wereldyogadag')->update(['description' => 'manual edit']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);

        $this->assertSame(
            'Op Wereldyogadag vieren we de kracht van yoga.',
            Theme::where('slug', 'wereldyogadag')->value('description'),
        );
    }

    public function test_unknown_recurrence_rule_fails(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'themes-bad');
        file_put_contents($tmp, json_encode([
            'themes' => [[
                'title' => 'Bad', 'slug' => 'bad', 'description' => 'x',
                'is_month' => false, 'recurrence_rule' => 'needs_verification',
                'recurrence_detail' => 'x',
            ]],
        ]));

        $this->artisan('themes:import', ['--file' => $tmp])->assertFailed();
        $this->assertDatabaseMissing('themes', ['slug' => 'bad']);

        @unlink($tmp);
    }

    public function test_links_fiches_by_slug(): void
    {
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);
        Fiche::factory()->create(['slug' => 'stoel-yoga-quiz']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);

        $theme = Theme::where('slug', 'wereldyogadag')->first();
        $this->assertCount(2, $theme->fiches);
        $this->assertEqualsCanonicalizing(
            ['yoga-voor-bewoners', 'stoel-yoga-quiz'],
            $theme->fiches->pluck('slug')->all(),
        );
    }

    public function test_unknown_fiche_slug_fails_and_leaves_fiche_theme_untouched(): void
    {
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);
        $trashed = Fiche::factory()->create(['slug' => 'stoel-yoga-quiz']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);
        $this->assertDatabaseCount('fiche_theme', 2);

        $trashed->delete();

        $this->artisan('themes:import', ['--file' => $this->fixturePath])
            ->expectsOutputToContain('bij thema wereldyogadag: stoel-yoga-quiz')
            ->assertFailed();

        $this->assertDatabaseCount('fiche_theme', 2);
        $this->assertDatabaseHas('fiche_theme', ['fiche_id' => $trashed->id]);
    }

    public function test_unknown_fiche_slug_on_fresh_database_creates_nothing(): void
    {
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertFailed();

        $this->assertDatabaseCount('themes', 0);
        $this->assertDatabaseCount('theme_occurrences', 0);
        $this->assertDatabaseCount('fiche_theme', 0);
    }

    public function test_allow_unknown_slugs_links_the_resolvable_slugs(): void
    {
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath, '--allow-unknown-slugs' => true])
            ->expectsOutputToContain('stoel-yoga-quiz')
            ->assertExitCode(0);

        $theme = Theme::where('slug', 'wereldyogadag')->first();
        $this->assertCount(1, $theme->fiches);
        $this->assertSame('yoga-voor-bewoners', $theme->fiches->first()->slug);
    }

    public function test_sync_removes_pivot_when_slug_is_removed_from_json(): void
    {
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);
        Fiche::factory()->create(['slug' => 'stoel-yoga-quiz']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);
        $this->assertCount(2, Theme::where('slug', 'wereldyogadag')->first()->fiches);

        $shrunken = tempnam(sys_get_temp_dir(), 'themes-shrunken');
        $payload = json_decode(file_get_contents($this->fixturePath), true);
        $payload['themes'][0]['fiche_slugs'] = ['yoga-voor-bewoners'];
        file_put_contents($shrunken, json_encode($payload));

        $this->artisan('themes:import', ['--file' => $shrunken])->assertExitCode(0);

        $this->assertCount(1, Theme::where('slug', 'wereldyogadag')->first()->fiches);
        @unlink($shrunken);
    }

    public function test_theme_without_fiche_slugs_key_keeps_existing_links(): void
    {
        $fiche = Fiche::factory()->create(['slug' => 'manual-link']);
        Fiche::factory()->create(['slug' => 'yoga-voor-bewoners']);
        Fiche::factory()->create(['slug' => 'stoel-yoga-quiz']);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);
        Theme::where('slug', 'zomer')->first()->fiches()->attach($fiche);

        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);

        $this->assertCount(1, Theme::where('slug', 'zomer')->first()->fiches);
    }
}
