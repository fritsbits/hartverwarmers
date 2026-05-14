<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_four_objectives(): void
    {
        $this->seed(OkrSeeder::class);

        $this->assertSame(
            ['presentatiekwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief'],
            Objective::orderBy('position')->pluck('slug')->all(),
        );
    }

    public function test_onboarding_has_five_key_results_in_funnel_order(): void
    {
        $this->seed(OkrSeeder::class);

        $onboarding = Objective::where('slug', 'onboarding')->firstOrFail();

        $this->assertSame([
            'onboarding_signup_count',
            'onboarding_verification_rate',
            'onboarding_return_7d_rate',
            'onboarding_interaction_30d_rate',
            'onboarding_followup_response_rate',
        ], $onboarding->keyResults->pluck('metric_key')->all());
    }

    public function test_seeder_does_not_set_target_values(): void
    {
        $this->seed(OkrSeeder::class);

        $this->assertSame(
            0,
            KeyResult::whereNotNull('target_value')->count(),
            'Seeder must not set target_value — strategic data lives in DB only after admin tinkers.',
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(OkrSeeder::class);
        $this->seed(OkrSeeder::class);

        $this->assertSame(4, Objective::count());
        $this->assertSame(8, KeyResult::count());
        $this->assertSame(3, Initiative::count());
    }
}
