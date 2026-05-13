<?php

namespace Tests\Unit\MonthlyDigest;

use App\Models\Fiche;
use App\Models\ThemeOccurrence;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_themes_in_next_30_days_limited_to_5(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        foreach ([3, 7, 14, 21, 28, 29] as $daysAhead) {
            ThemeOccurrence::factory()->create([
                'year' => 2026,
                'start_date' => now()->addDays($daysAhead),
            ]);
        }

        ThemeOccurrence::factory()->create([
            'year' => 2026,
            'start_date' => now()->addDays(40),
        ]);

        $payload = app(Composer::class)->compose(now());

        $this->assertCount(5, $payload->themes);
        $this->assertSame(5, $payload->upcomingThemeCount);
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
}
