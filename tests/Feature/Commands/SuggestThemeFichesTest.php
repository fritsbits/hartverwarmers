<?php

namespace Tests\Feature\Commands;

use App\Ai\Agents\ThemeFicheMatchAgent;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SuggestThemeFichesTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.providers.anthropic.key' => 'test-key']);

        $this->path = storage_path('framework/testing/themes-'.uniqid().'.json');
    }

    protected function tearDown(): void
    {
        File::delete($this->path);

        parent::tearDown();
    }

    public function test_it_writes_suggested_slugs_into_the_json(): void
    {
        $fiche = Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag'),
        ]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past bij de dag']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertSuccessful();

        $this->assertSame(['kaarsen-maken'], $this->themeIn('baarddag')['fiche_slugs']);
        $this->assertTrue($fiche->exists);
    }

    public function test_it_prompts_with_the_theme_and_the_fiche_catalogue(): void
    {
        Fiche::factory()->published()->create([
            'slug' => 'kaarsen-maken',
            'title' => 'Kaarsen maken met bewoners',
        ]);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag', 'Een dag over baarden.'),
        ]);

        ThemeFicheMatchAgent::fake([['matches' => []]]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertSuccessful();

        ThemeFicheMatchAgent::assertPrompted(
            fn ($prompt) => $prompt->contains('Baarddag')
                && $prompt->contains('Een dag over baarden.')
                && $prompt->contains('kaarsen-maken')
                && $prompt->contains('Kaarsen maken met bewoners')
        );
    }

    public function test_it_drops_slugs_that_do_not_exist(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [
                ['slug' => 'kaarsen-maken', 'reason' => 'past'],
                ['slug' => 'deze-fiche-bestaat-niet', 'reason' => 'verzonnen'],
            ]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->expectsOutputToContain('deze-fiche-bestaat-niet')
            ->assertSuccessful();

        $this->assertSame(['kaarsen-maken'], $this->themeIn('baarddag')['fiche_slugs']);
    }

    public function test_it_ignores_unpublished_fiches(): void
    {
        Fiche::factory()->create(['slug' => 'concept-fiche', 'published' => false]);
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [
                ['slug' => 'concept-fiche', 'reason' => 'past'],
                ['slug' => 'kaarsen-maken', 'reason' => 'past'],
            ]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertSuccessful();

        ThemeFicheMatchAgent::assertPrompted(fn ($prompt) => ! $prompt->contains('concept-fiche'));
        $this->assertSame(['kaarsen-maken'], $this->themeIn('baarddag')['fiche_slugs']);
    }

    public function test_an_empty_answer_leaves_the_theme_without_the_key(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('cyber-monday', 'Cyber Monday')]);

        ThemeFicheMatchAgent::fake([['matches' => []]]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertSuccessful();

        $this->assertArrayNotHasKey('fiche_slugs', $this->themeIn('cyber-monday'));
    }

    public function test_it_caps_the_number_of_slugs_per_theme(): void
    {
        foreach (['een', 'twee', 'drie'] as $slug) {
            Fiche::factory()->published()->create(['slug' => $slug]);
        }

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [
                ['slug' => 'een', 'reason' => 'a'],
                ['slug' => 'twee', 'reason' => 'b'],
                ['slug' => 'drie', 'reason' => 'c'],
            ]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path, '--max' => 2])
            ->assertSuccessful();

        $this->assertSame(['een', 'twee'], $this->themeIn('baarddag')['fiche_slugs']);
    }

    public function test_it_skips_themes_that_already_have_fiche_slugs(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag') + ['fiche_slugs' => ['met-de-hand-gekozen']],
        ]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertSuccessful();

        ThemeFicheMatchAgent::assertNeverPrompted();
        $this->assertSame(['met-de-hand-gekozen'], $this->themeIn('baarddag')['fiche_slugs']);
    }

    public function test_force_overwrites_themes_that_already_have_fiche_slugs(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag') + ['fiche_slugs' => ['met-de-hand-gekozen']],
        ]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(['kaarsen-maken'], $this->themeIn('baarddag')['fiche_slugs']);
    }

    public function test_slug_option_limits_the_run_to_one_theme(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag'),
            $this->theme('dierendag', 'Dierendag'),
        ]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path, '--slug' => ['dierendag']])
            ->assertSuccessful();

        $this->assertArrayNotHasKey('fiche_slugs', $this->themeIn('baarddag'));
        $this->assertSame(['kaarsen-maken'], $this->themeIn('dierendag')['fiche_slugs']);
    }

    public function test_dry_run_leaves_the_file_untouched(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);
        $before = File::get($this->path);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame($before, File::get($this->path));
    }

    public function test_a_failing_ai_call_leaves_the_other_themes_intact(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag'),
            $this->theme('dierendag', 'Dierendag'),
        ]);

        ThemeFicheMatchAgent::fake(function ($prompt) {
            if (str_contains((string) $prompt, 'Baarddag')) {
                throw new \RuntimeException('api down');
            }

            return ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]];
        });

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertSuccessful();

        $this->assertArrayNotHasKey('fiche_slugs', $this->themeIn('baarddag'));
        $this->assertSame(['kaarsen-maken'], $this->themeIn('dierendag')['fiche_slugs']);
    }

    public function test_it_fails_without_an_anthropic_key(): void
    {
        config(['ai.providers.anthropic.key' => null]);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])
            ->assertFailed();
    }

    public function test_it_fails_on_a_missing_file(): void
    {
        $this->artisan('themes:suggest-fiches', ['--file' => $this->path.'-weg'])
            ->assertFailed();
    }

    public function test_it_keeps_the_file_importable_and_two_space_indented(): void
    {
        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])->assertSuccessful();

        $written = File::get($this->path);

        $this->assertStringContainsString("\n  \"themes\": [\n", $written);
        $this->assertStringEndsWith("\n", $written);

        $this->artisan('themes:import', ['--file' => $this->path])->assertSuccessful();

        $this->assertDatabaseCount('fiche_theme', 1);
    }

    public function test_a_finished_round_stamps_the_watermark_with_today(): void
    {
        $this->travelTo('2026-10-05 10:00:00');

        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')]);

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])->assertSuccessful();

        $this->assertSame('2026-10-05', $this->watermark());
    }

    public function test_the_watermark_stays_the_first_key_when_the_file_is_rewritten(): void
    {
        $this->travelTo('2026-10-05 10:00:00');

        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([$this->theme('baarddag', 'Baarddag')], '2026-09-01');

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path])->assertSuccessful();

        $this->assertSame('2026-10-05', $this->watermark());
        $this->assertStringStartsWith("{\n  \"fiche_match_watermark\": \"2026-10-05\",\n  \"themes\": [\n", File::get($this->path));
    }

    public function test_a_round_limited_to_some_slugs_leaves_the_watermark_alone(): void
    {
        $this->travelTo('2026-10-05 10:00:00');

        Fiche::factory()->published()->create(['slug' => 'kaarsen-maken']);

        $this->writeThemes([
            $this->theme('baarddag', 'Baarddag'),
            $this->theme('dierendag', 'Dierendag'),
        ], '2026-09-01');

        ThemeFicheMatchAgent::fake([
            ['matches' => [['slug' => 'kaarsen-maken', 'reason' => 'past']]],
        ]);

        $this->artisan('themes:suggest-fiches', ['--file' => $this->path, '--slug' => ['dierendag']])
            ->assertSuccessful();

        $this->assertSame(['kaarsen-maken'], $this->themeIn('dierendag')['fiche_slugs']);
        $this->assertSame('2026-09-01', $this->watermark());
    }

    /**
     * @return array<string, mixed>
     */
    private function theme(string $slug, string $title, string $description = 'Een thema.'): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'is_month' => false,
            'recurrence_rule' => 'fixed',
            'recurrence_detail' => 'Fixed: month-day 09-02',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $themes
     */
    private function writeThemes(array $themes, ?string $watermark = null): void
    {
        $data = $watermark === null ? [] : ['fiche_match_watermark' => $watermark];
        $data['themes'] = $themes;

        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function watermark(): ?string
    {
        return json_decode(File::get($this->path), true)['fiche_match_watermark'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function themeIn(string $slug): array
    {
        $data = json_decode(File::get($this->path), true);

        foreach ($data['themes'] as $theme) {
            if ($theme['slug'] === $slug) {
                return $theme;
            }
        }

        $this->fail("Thema {$slug} niet gevonden in het bestand.");
    }
}
