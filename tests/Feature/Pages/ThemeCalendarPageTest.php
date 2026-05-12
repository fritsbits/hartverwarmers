<?php

namespace Tests\Feature\Pages;

use App\Models\Fiche;
use App\Models\Initiative;
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

    public function test_multi_day_theme_renders_date_range(): void
    {
        $theme = Theme::factory()->create(['title' => 'Ronde van Frankrijk']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-07-04', 'end_date' => '2026-07-26',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-07']));
        $response->assertOk()->assertSee('t/m 26 juli');
    }

    public function test_season_theme_renders_as_banner_above_day_themes(): void
    {
        $season = Theme::factory()->season()->create(['title' => 'Zomer']);
        ThemeOccurrence::factory()->for($season)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $day = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($day)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $html = $response->getContent();

        $this->assertLessThan(strpos($html, 'Wereldyogadag'), strpos($html, 'Zomer'),
            'Season banner should render above day themes.');
    }

    public function test_empty_month_shows_warm_state(): void
    {
        $response = $this->get(route('themes.index', ['maand' => '2026-01']));
        $response->assertOk()->assertSee('Geen thema\'s', false);
    }

    public function test_linked_fiches_render_as_cards(): void
    {
        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $initiative = Initiative::factory()->published()->create(['slug' => 'inspiratie']);
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Yoga voor bewoners',
            'slug' => 'yoga-voor-bewoners',
        ]);
        $theme->fiches()->attach($fiche);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertOk()->assertSee('Yoga voor bewoners')
            ->assertSee(route('fiches.show', [$initiative, $fiche]));
    }

    public function test_theme_without_fiches_shows_empty_state_cta(): void
    {
        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertSee('Deel je activiteit')->assertSee(route('fiches.create'));
    }

    public function test_prev_and_next_month_links_present(): void
    {
        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertSee(route('themes.index', ['maand' => '2026-05']));
        $response->assertSee(route('themes.index', ['maand' => '2026-07']));
    }

    public function test_theme_anchor_id_present(): void
    {
        $theme = Theme::factory()->create(['title' => 'Wereldyogadag', 'slug' => 'wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertSee('id="thema-wereldyogadag"', false);
    }

    public function test_h1_is_the_month_label(): void
    {
        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertSee('<h1', false)->assertSee('juni 2026');
    }

    public function test_renders_monthly_intro_when_available(): void
    {
        $response = $this->get(route('themes.index', ['maand' => '2026-06']));

        $monthIntro = $response->viewData('monthIntro');
        $this->assertNotNull($monthIntro);
        $this->assertSame('Licht binnen', $monthIntro['title']);
        $response->assertSee('Vaders worden gevierd');
    }

    public function test_today_theme_renders_vandaag_badge(): void
    {
        Carbon::setTestNow('2026-06-21 09:00:00');

        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertSee('Vandaag');

        Carbon::setTestNow();
    }

    public function test_tomorrow_theme_renders_morgen_badge(): void
    {
        Carbon::setTestNow('2026-06-20 09:00:00');

        $theme = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));
        $response->assertSee('Morgen');

        Carbon::setTestNow();
    }

    public function test_mini_calendar_links_themed_days_to_theme_anchor(): void
    {
        $theme = Theme::factory()->create(['title' => 'Wereldyogadag', 'slug' => 'wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('themes.index', ['maand' => '2026-06']));

        // The mini-calendar should include a link to the theme anchor.
        $expectedHref = route('themes.index', ['maand' => '2026-06']).'#thema-wereldyogadag';
        $response->assertSee($expectedHref, false);
    }

    public function test_top_month_nav_hides_prev_when_entirely_past(): void
    {
        // Today is the project's fixed date (2026-05-12). April 2026 is entirely past.
        $response = $this->get(route('themes.index', ['maand' => '2026-05']));

        $response->assertViewHas('showPrev', false);
    }

    public function test_top_month_nav_shows_prev_when_overlaps_today(): void
    {
        // Viewing June; previous month (May) contains today, so prev is shown.
        $response = $this->get(route('themes.index', ['maand' => '2026-06']));

        $response->assertViewHas('showPrev', true);
    }
}
