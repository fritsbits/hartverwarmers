<?php

namespace Tests\Unit\Models;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeOccurrenceBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function occurrence(string $start, ?string $end = null): ThemeOccurrence
    {
        return ThemeOccurrence::factory()->for(Theme::factory())->create([
            'year' => (int) substr($start, 0, 4),
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    public function test_today_returns_vandaag_emphatic(): void
    {
        $occ = $this->occurrence('2026-06-15');
        $badge = $occ->relativeBadge(CarbonImmutable::create(2026, 6, 15));

        $this->assertSame(['label' => 'Vandaag', 'emphatic' => true], $badge);
    }

    public function test_tomorrow_returns_morgen(): void
    {
        $occ = $this->occurrence('2026-06-16');
        $badge = $occ->relativeBadge(CarbonImmutable::create(2026, 6, 15));

        $this->assertSame(['label' => 'Morgen', 'emphatic' => false], $badge);
    }

    public function test_two_days_out_returns_null(): void
    {
        $occ = $this->occurrence('2026-06-17');

        $this->assertNull($occ->relativeBadge(CarbonImmutable::create(2026, 6, 15)));
    }

    public function test_past_returns_null(): void
    {
        $occ = $this->occurrence('2026-06-14');

        $this->assertNull($occ->relativeBadge(CarbonImmutable::create(2026, 6, 15)));
    }

    public function test_multi_day_currently_active_returns_loopt_nu(): void
    {
        $occ = $this->occurrence('2026-07-04', '2026-07-26');
        $badge = $occ->relativeBadge(CarbonImmutable::create(2026, 7, 15));

        $this->assertSame(['label' => 'Loopt nu', 'emphatic' => true], $badge);
    }

    public function test_multi_day_on_start_day_returns_vandaag(): void
    {
        $occ = $this->occurrence('2026-07-04', '2026-07-26');
        $badge = $occ->relativeBadge(CarbonImmutable::create(2026, 7, 4));

        $this->assertSame(['label' => 'Vandaag', 'emphatic' => true], $badge);
    }
}
