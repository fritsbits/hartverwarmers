<?php

namespace Tests\Feature\MonthlyDigest;

use App\Models\Fiche;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_themes_in_next_30_days_limited_to_5(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        foreach ([3, 7, 14, 21, 28, 29] as $daysAhead) {
            $this->occurrenceWithPublishedFiche(now()->addDays($daysAhead));
        }

        $this->occurrenceWithPublishedFiche(now()->addDays(40));

        $payload = app(Composer::class)->compose(now());

        $this->assertCount(5, $payload->themes);
        $this->assertSame(6, $payload->upcomingThemeCount);
    }

    public function test_returns_most_recent_published_diamond(): void
    {
        Fiche::factory()->published()->create(['has_diamond' => true, 'created_at' => now()->subDays(60)]);
        $recent = Fiche::factory()->published()->create(['has_diamond' => true, 'created_at' => now()->subDays(10)]);
        Fiche::factory()->published()->create(['has_diamond' => false, 'created_at' => now()->subDays(5)]);

        $payload = app(Composer::class)->compose(now());

        $this->assertNotNull($payload->diamond);
        $this->assertSame($recent->id, $payload->diamond->id);
    }

    public function test_returns_6_most_recent_published_fiches_in_window(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        for ($i = 0; $i < 8; $i++) {
            Fiche::factory()->published()->create(['created_at' => now()->subDays($i)]);
        }

        Fiche::factory()->published()->create(['created_at' => now()->subDays(45)]);

        $payload = app(Composer::class)->compose(now());

        $this->assertCount(6, $payload->recentFiches);
        $this->assertSame(8, $payload->newFicheCount);
    }

    public function test_diamond_must_be_published(): void
    {
        Fiche::factory()->create(['has_diamond' => true, 'published' => false]);

        $payload = app(Composer::class)->compose(now());

        $this->assertNull($payload->diamond);
    }

    public function test_diamond_ordered_by_award_time_not_fiche_creation(): void
    {
        Carbon::setTestNow('2026-05-13 12:00:00');

        Fiche::factory()->published()->create([
            'has_diamond' => true,
            'created_at' => now()->subMonths(1),
            'diamond_awarded_at' => now()->subMonths(1),
        ]);
        $recentlyAwarded = Fiche::factory()->published()->create([
            'has_diamond' => true,
            'created_at' => now()->subYears(2),
            'diamond_awarded_at' => now()->subHour(),
        ]);

        $payload = app(Composer::class)->compose(now());

        $this->assertSame($recentlyAwarded->id, $payload->diamond->id);
    }

    public function test_upcoming_theme_count_returns_total_not_just_displayed(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        // 7 occurrences in window, each on a different theme (since (theme_id, year) is unique)
        for ($i = 0; $i < 7; $i++) {
            $this->occurrenceWithPublishedFiche(now()->addDays(3 + $i));
        }

        $payload = app(Composer::class)->compose(now());

        $this->assertCount(5, $payload->themes, 'themes collection should be capped at 5');
        $this->assertSame(7, $payload->upcomingThemeCount, 'upcomingThemeCount should reflect the true total');
    }

    public function test_excludes_occurrences_whose_theme_has_no_published_fiches(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $withFiche = $this->occurrenceWithPublishedFiche(now()->addDays(3));

        $unpublishedOnly = ThemeOccurrence::factory()->create(['year' => 2026, 'start_date' => now()->addDays(5)]);
        $unpublishedOnly->theme->fiches()->attach(Fiche::factory()->create(['published' => false]));

        $trashedOnly = ThemeOccurrence::factory()->create(['year' => 2026, 'start_date' => now()->addDays(7)]);
        $trashedFiche = Fiche::factory()->published()->create();
        $trashedOnly->theme->fiches()->attach($trashedFiche);
        $trashedFiche->delete();

        ThemeOccurrence::factory()->create(['year' => 2026, 'start_date' => now()->addDays(9)]);

        $payload = app(Composer::class)->compose(now());

        $this->assertCount(1, $payload->themes);
        $this->assertTrue($payload->themes->first()->is($withFiche));
        $this->assertSame(1, $payload->themes->first()->theme->fiches_count);
        $this->assertSame(1, $payload->upcomingThemeCount);
    }

    public function test_compose_includes_latest_fresh_product_update(): void
    {
        Storage::fake('content');
        Storage::disk('content')->put('updates/2026-05-vers.json', json_encode([
            'uid' => '2026-05-vers',
            'published_at' => '2026-05-01',
            'title' => 'Verse update',
            'body' => 'Korte tekst.',
        ]));

        $payload = app(Composer::class)->compose(Carbon::parse('2026-05-13 08:00:00'));

        $this->assertSame('2026-05-vers', $payload->productUpdate['uid']);
    }

    public function test_compose_leaves_product_update_null_when_newest_is_stale(): void
    {
        Storage::fake('content');
        Storage::disk('content')->put('updates/2026-01-oud.json', json_encode([
            'uid' => '2026-01-oud',
            'published_at' => '2026-01-01',
            'title' => 'Oude update',
            'body' => 'Korte tekst.',
        ]));

        $payload = app(Composer::class)->compose(Carbon::parse('2026-05-13 08:00:00'));

        $this->assertNull($payload->productUpdate);
    }

    /**
     * An occurrence in the given window whose theme carries one published
     * fiche, so it survives Composer's empty-theme filter.
     */
    private function occurrenceWithPublishedFiche(Carbon $startDate): ThemeOccurrence
    {
        $occurrence = ThemeOccurrence::factory()
            ->for(Theme::factory()->create())
            ->create(['year' => $startDate->year, 'start_date' => $startDate]);

        $occurrence->theme->fiches()->attach(Fiche::factory()->published()->create());

        return $occurrence;
    }
}
