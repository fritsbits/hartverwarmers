<?php

namespace Tests\Feature\Pages;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ThemeCalendarPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_renders_current_month(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index'));
        $response->assertOk()->assertSee('Wereldyogadag');

        Carbon::setTestNow();
    }

    public function test_query_string_selects_month(): void
    {
        $juneTheme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($juneTheme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $julyTheme = Theme::factory()->create(['title' => 'Wereld Chocoladedag']);
        ThemeOccurrence::factory()->for($julyTheme)->create([
            'year' => 2026, 'start_date' => '2026-07-07',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-07']));
        $response->assertOk()
            ->assertSee('Wereld Chocoladedag')
            ->assertDontSee('Wereldyogadag');
    }

    public function test_invalid_month_falls_back_to_current(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $this->get(route('themes.index', ['maand' => 'banana']))->assertOk()->assertSee('Wereldyogadag');
        $this->get(route('themes.index', ['maand' => '2026-13']))->assertOk()->assertSee('Wereldyogadag');

        Carbon::setTestNow();
    }

    public function test_passes_month_context_to_view(): void
    {
        $response = $this->get(route('themes.index', ['maand' => '2026-07']));

        $response->assertViewHas('month', fn ($m) => $m->year === 2026 && $m->month === 7);
        $response->assertViewHas('seasonThemes');
        $response->assertViewHas('dayThemes');
    }
}
