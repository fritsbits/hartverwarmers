<?php

namespace Tests\Feature\Models;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_theme(): void
    {
        $theme = Theme::factory()->create();
        $occurrence = ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026,
            'start_date' => '2026-06-21',
            'end_date' => null,
        ]);

        $this->assertTrue($occurrence->theme->is($theme));
    }

    public function test_theme_has_many_occurrences(): void
    {
        $theme = Theme::factory()->create();
        ThemeOccurrence::factory()->for($theme)->create(['year' => 2025, 'start_date' => '2025-06-21']);
        ThemeOccurrence::factory()->for($theme)->create(['year' => 2026, 'start_date' => '2026-06-21']);

        $this->assertCount(2, $theme->occurrences);
    }

    public function test_year_is_unique_per_theme(): void
    {
        $theme = Theme::factory()->create();
        ThemeOccurrence::factory()->for($theme)->create(['year' => 2026, 'start_date' => '2026-06-21']);

        $this->expectException(QueryException::class);
        ThemeOccurrence::factory()->for($theme)->create(['year' => 2026, 'start_date' => '2026-07-01']);
    }

    public function test_dates_cast_to_carbon(): void
    {
        $theme = Theme::factory()->create();
        $occurrence = ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026,
            'start_date' => '2026-06-21',
            'end_date' => '2026-06-23',
        ]);

        $this->assertInstanceOf(CarbonInterface::class, $occurrence->start_date);
        $this->assertInstanceOf(CarbonInterface::class, $occurrence->end_date);
    }
}
