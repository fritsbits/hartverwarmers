<?php

namespace Tests\Feature\Pages;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ThemePrintPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_month_with_day_theme_and_description(): void
    {
        $theme = Theme::factory()->create([
            'title' => 'Wereldyogadag',
            'description' => 'Rustige rek- en strekoefeningen voor bewoners.',
        ]);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.print', ['maand' => '2026-06']));

        $response->assertOk()
            ->assertSee('juni 2026')
            ->assertSee('Wereldyogadag')
            ->assertSee('Rustige rek- en strekoefeningen voor bewoners.');
    }

    public function test_query_string_selects_month(): void
    {
        $juneTheme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($juneTheme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.print', ['maand' => '2026-07']));

        $response->assertOk()
            ->assertSee('juli 2026')
            ->assertDontSee('Wereldyogadag');
    }

    public function test_invalid_month_falls_back_to_current(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $this->get(route('themes.print', ['maand' => 'banana']))->assertOk()->assertSee('juni 2026');
        $this->get(route('themes.print', ['maand' => '2026-13']))->assertOk()->assertSee('juni 2026');

        Carbon::setTestNow();
    }

    public function test_multi_day_theme_shows_end_date(): void
    {
        $theme = Theme::factory()->create(['title' => 'Ronde van Frankrijk']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-07-04', 'end_date' => '2026-07-26',
        ]);

        $response = $this->get(route('themes.print', ['maand' => '2026-07']));

        $response->assertOk()->assertSee('t/m 26 juli');
    }

    public function test_season_theme_renders_in_season_band(): void
    {
        $season = Theme::factory()->season()->create(['title' => 'Zomer']);
        ThemeOccurrence::factory()->for($season)->create([
            'year' => 2026, 'start_date' => '2026-06-21', 'end_date' => '2026-09-20',
        ]);

        $response = $this->get(route('themes.print', ['maand' => '2026-06']));

        $response->assertOk()
            ->assertSee('Zomer')
            ->assertSee('21 juni – 20 september');
    }

    public function test_sheet_uses_a3_landscape_page_size(): void
    {
        $response = $this->get(route('themes.print', ['maand' => '2026-06']));

        $response->assertOk()->assertSee('size: A3 landscape', false);
    }

    public function test_calendar_page_links_to_print_version(): void
    {
        $response = $this->get(route('themes.index', ['maand' => '2026-06']));

        $response->assertOk()
            ->assertSee('Print deze kalender')
            ->assertSee(route('themes.print', ['maand' => '2026-06']));
    }
}
