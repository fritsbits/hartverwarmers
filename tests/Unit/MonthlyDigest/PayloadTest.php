<?php

namespace Tests\Unit\MonthlyDigest;

use App\Models\ThemeOccurrence;
use App\Services\MonthlyDigest\Payload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_holds_all_fields(): void
    {
        $themes = new Collection;
        $diamond = null;
        $fiches = new Collection;

        $payload = new Payload(
            themes: $themes,
            diamond: $diamond,
            recentFiches: $fiches,
            upcomingThemeCount: 5,
            newFicheCount: 12,
            sentAt: Carbon::parse('2026-05-13 08:00:00'),
        );

        $this->assertSame(5, $payload->upcomingThemeCount);
        $this->assertSame(12, $payload->newFicheCount);
        $this->assertTrue($payload->sentAt->equalTo(Carbon::parse('2026-05-13 08:00:00')));
    }

    public function test_is_empty_when_no_themes_and_no_fiches(): void
    {
        $payload = new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 0,
            sentAt: now(),
        );

        $this->assertTrue($payload->isEmpty());
    }

    public function test_is_not_empty_when_only_themes_present(): void
    {
        $payload = new Payload(
            themes: new Collection([new ThemeOccurrence]),
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 1,
            newFicheCount: 0,
            sentAt: now(),
        );

        $this->assertFalse($payload->isEmpty());
    }
}
