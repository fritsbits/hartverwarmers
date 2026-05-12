<?php

namespace Tests\Feature\Commands;

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

    public function test_imports_themes_and_occurrences(): void
    {
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
        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);
        $this->artisan('themes:import', ['--file' => $this->fixturePath])->assertExitCode(0);

        $this->assertDatabaseCount('themes', 2);
        $this->assertDatabaseCount('theme_occurrences', 2);
    }

    public function test_updates_changed_theme_fields_on_reimport(): void
    {
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
}
