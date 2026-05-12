<?php

namespace Tests\Unit\Models;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_month_returns_single_day_in_target_month(): void
    {
        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21', 'end_date' => null,
        ]);

        $results = Theme::forMonth(2026, 6)->get();

        $this->assertTrue($results->contains($theme));
    }

    public function test_for_month_excludes_themes_outside_window(): void
    {
        $theme = Theme::factory()->create();
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-05-12', 'end_date' => null,
        ]);

        $this->assertCount(0, Theme::forMonth(2026, 6)->get());
    }

    public function test_for_month_includes_multi_day_overlap(): void
    {
        $theme = Theme::factory()->create(['title' => 'Ronde van Frankrijk']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-28', 'end_date' => '2026-07-20',
        ]);

        $this->assertTrue(Theme::forMonth(2026, 6)->get()->contains($theme));
        $this->assertTrue(Theme::forMonth(2026, 7)->get()->contains($theme));
        $this->assertFalse(Theme::forMonth(2026, 5)->get()->contains($theme));
        $this->assertFalse(Theme::forMonth(2026, 8)->get()->contains($theme));
    }

    public function test_for_month_treats_null_end_date_as_single_day(): void
    {
        $theme = Theme::factory()->season()->create(['title' => 'Zomer']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21', 'end_date' => null,
        ]);

        $this->assertTrue(Theme::forMonth(2026, 6)->get()->contains($theme));
        $this->assertFalse(Theme::forMonth(2026, 7)->get()->contains($theme));
    }

    public function test_for_month_does_not_duplicate_rows_when_multiple_year_occurrences_exist(): void
    {
        $theme = Theme::factory()->create();
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2025, 'start_date' => '2025-06-21', 'end_date' => null,
        ]);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21', 'end_date' => null,
        ]);

        $this->assertCount(1, Theme::forMonth(2026, 6)->get());
    }

    public function test_for_month_includes_occurrence_spanning_entire_month(): void
    {
        $theme = Theme::factory()->create(['title' => 'Zomervakantie']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-15', 'end_date' => '2026-09-01',
        ]);

        $this->assertTrue(Theme::forMonth(2026, 7)->get()->contains($theme));
        $this->assertTrue(Theme::forMonth(2026, 8)->get()->contains($theme));
    }
}
