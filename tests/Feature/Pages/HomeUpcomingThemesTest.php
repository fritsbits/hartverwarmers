<?php

namespace Tests\Feature\Pages;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeUpcomingThemesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow('2026-05-12 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_passes_upcoming_themes_to_view(): void
    {
        $next = Theme::factory()->create(['title' => 'Wereldyogadag']);
        ThemeOccurrence::factory()->for($next)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $upcoming = $response->viewData('upcomingThemes');
        $this->assertNotNull($upcoming);
        $this->assertCount(1, $upcoming);
        $this->assertEquals('Wereldyogadag', $upcoming->first()->theme->title);
    }

    public function test_caps_to_three_upcoming(): void
    {
        foreach (range(1, 5) as $i) {
            $t = Theme::factory()->create();
            ThemeOccurrence::factory()->for($t)->create([
                'year' => 2026, 'start_date' => "2026-06-0{$i}",
            ]);
        }

        $response = $this->get(route('home'));
        $this->assertCount(3, $response->viewData('upcomingThemes'));
    }

    public function test_excludes_past_single_day_occurrences(): void
    {
        $past = Theme::factory()->create(['title' => 'Past']);
        ThemeOccurrence::factory()->for($past)->create([
            'year' => 2026, 'start_date' => '2026-05-01',
        ]);

        $future = Theme::factory()->create(['title' => 'Future']);
        ThemeOccurrence::factory()->for($future)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $upcoming = $this->get(route('home'))->viewData('upcomingThemes');
        $this->assertEquals(['Future'], $upcoming->pluck('theme.title')->all());
    }

    public function test_excludes_currently_active_multi_day(): void
    {
        $active = Theme::factory()->create(['title' => 'Active']);
        ThemeOccurrence::factory()->for($active)->create([
            'year' => 2026, 'start_date' => '2026-05-01', 'end_date' => '2026-05-31',
        ]);

        $upcoming = $this->get(route('home'))->viewData('upcomingThemes');
        $this->assertFalse($upcoming->pluck('theme.title')->contains('Active'));
    }

    public function test_excludes_themes_starting_today(): void
    {
        // Carbon::setTestNow is '2026-05-12 09:00:00' from setUp, so today = 2026-05-12.
        $todayTheme = Theme::factory()->create(['title' => 'Today only']);
        ThemeOccurrence::factory()->for($todayTheme)->create([
            'year' => 2026, 'start_date' => '2026-05-12',
        ]);

        $tomorrowTheme = Theme::factory()->create(['title' => 'Tomorrow']);
        ThemeOccurrence::factory()->for($tomorrowTheme)->create([
            'year' => 2026, 'start_date' => '2026-05-13',
        ]);

        $upcoming = $this->get(route('home'))->viewData('upcomingThemes');
        $this->assertEquals(['Tomorrow'], $upcoming->pluck('theme.title')->all());
    }

    public function test_block_renders_with_links_and_calendar_cta(): void
    {
        $theme = Theme::factory()->create(['title' => 'Wereldyogadag', 'slug' => 'wereldyogadag']);
        ThemeOccurrence::factory()->for($theme)->create([
            'year' => 2026, 'start_date' => '2026-06-21',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('Binnenkort')
            ->assertSee('Wereldyogadag')
            ->assertSee(route('themes.index', ['maand' => '2026-06']).'#thema-wereldyogadag')
            ->assertSee(route('themes.index'));
    }

    public function test_block_is_hidden_when_no_upcoming_themes(): void
    {
        $response = $this->get(route('home'));
        $response->assertDontSee('Binnenkort');
    }
}
