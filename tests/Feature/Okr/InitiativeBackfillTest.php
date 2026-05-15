<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_sets_started_at_for_three_existing_initiatives(): void
    {
        // RefreshDatabase has already run every migration (incl. the backfill) against
        // the test DB. Seeding the OKR data here, then re-running the backfill migration's
        // up() logic, verifies the dates are applied idempotently.
        $this->seed(OkrSeeder::class);

        $migration = require database_path('migrations/2026_05_15_000003_backfill_okr_initiative_started_at.php');
        $migration->up();

        $this->assertSame(
            '2026-03-17',
            Initiative::where('slug', 'ai-suggesties')->first()->started_at->toDateString(),
        );
        $this->assertSame(
            '2026-04-02',
            Initiative::where('slug', 'onboarding-emails')->first()->started_at->toDateString(),
        );
        $this->assertSame(
            '2026-05-13',
            Initiative::where('slug', 'nieuwsbrief-systeem')->first()->started_at->toDateString(),
        );
    }

    public function test_backfill_captures_baselines_for_seeded_initiatives(): void
    {
        $this->seed(OkrSeeder::class);

        $migration = require database_path('migrations/2026_05_15_000003_backfill_okr_initiative_started_at.php');
        $migration->up();

        $aiSuggesties = Initiative::where('slug', 'ai-suggesties')->first();
        // ai-suggesties' objective (presentatiekwaliteit) has 1 KR → 1 baseline row.
        $this->assertGreaterThanOrEqual(1, $aiSuggesties->baselines()->count());
    }
}
