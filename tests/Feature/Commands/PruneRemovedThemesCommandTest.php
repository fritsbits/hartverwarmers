<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PruneRemovedThemesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = storage_path('framework/testing/themes-'.uniqid().'.json');
    }

    protected function tearDown(): void
    {
        File::delete($this->path);

        parent::tearDown();
    }

    public function test_it_deletes_themes_that_no_longer_appear_in_the_json(): void
    {
        Theme::factory()->create(['slug' => 'blijft']);
        Theme::factory()->create(['slug' => 'weg']);

        $this->writeThemes(['blijft']);

        $this->artisan('themes:prune-removed', ['--file' => $this->path, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('themes', ['slug' => 'blijft']);
        $this->assertDatabaseMissing('themes', ['slug' => 'weg']);
    }

    public function test_it_changes_nothing_without_the_force_flag(): void
    {
        Theme::factory()->create(['slug' => 'weg']);

        $this->writeThemes(['blijft']);

        $this->artisan('themes:prune-removed', ['--file' => $this->path])
            ->expectsOutputToContain('Voorvertoning')
            ->assertSuccessful();

        $this->assertDatabaseHas('themes', ['slug' => 'weg']);
    }

    public function test_deleting_a_theme_takes_its_dates_and_fiche_links_but_spares_the_fiche(): void
    {
        $theme = Theme::factory()->create(['slug' => 'weg']);
        $fiche = Fiche::factory()->published()->create();

        $theme->fiches()->attach($fiche);
        ThemeOccurrence::create([
            'theme_id' => $theme->id,
            'year' => 2026,
            'start_date' => '2026-11-19',
        ]);

        $this->writeThemes(['blijft']);
        Theme::factory()->create(['slug' => 'blijft']);

        $this->artisan('themes:prune-removed', ['--file' => $this->path, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('theme_occurrences', ['theme_id' => $theme->id]);
        $this->assertDatabaseMissing('fiche_theme', ['theme_id' => $theme->id]);
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id]);
    }

    public function test_it_refuses_when_more_themes_would_go_than_the_max_allows(): void
    {
        Theme::factory()->count(3)->create();

        $this->writeThemes(['blijft']);

        $this->artisan('themes:prune-removed', [
            '--file' => $this->path,
            '--force' => true,
            '--max' => 2,
        ])->assertFailed();

        $this->assertSame(3, Theme::count());
    }

    public function test_it_refuses_a_json_without_a_single_theme(): void
    {
        Theme::factory()->create(['slug' => 'weg']);

        File::put($this->path, json_encode(['themes' => []]));

        $this->artisan('themes:prune-removed', ['--file' => $this->path, '--force' => true])
            ->assertFailed();

        $this->assertDatabaseHas('themes', ['slug' => 'weg']);
    }

    public function test_it_reports_when_there_is_nothing_to_prune(): void
    {
        Theme::factory()->create(['slug' => 'blijft']);

        $this->writeThemes(['blijft']);

        $this->artisan('themes:prune-removed', ['--file' => $this->path, '--force' => true])
            ->expectsOutputToContain('Niets te verwijderen')
            ->assertSuccessful();

        $this->assertDatabaseHas('themes', ['slug' => 'blijft']);
    }

    public function test_it_fails_on_a_missing_file(): void
    {
        $this->artisan('themes:prune-removed', ['--file' => $this->path.'-bestaat-niet'])
            ->assertFailed();
    }

    /**
     * @param  list<string>  $slugs
     */
    private function writeThemes(array $slugs): void
    {
        File::put($this->path, json_encode([
            'themes' => array_map(fn (string $slug): array => [
                'title' => ucfirst($slug),
                'slug' => $slug,
                'recurrence_rule' => 'fixed',
            ], $slugs),
        ]));
    }
}
