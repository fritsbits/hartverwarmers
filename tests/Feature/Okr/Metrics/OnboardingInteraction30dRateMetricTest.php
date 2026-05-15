<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\OnboardingInteraction30dRateMetric;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingInteraction30dRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_returns_interaction_rate_for_cohort(): void
    {
        $fiche = Fiche::factory()->create();

        $userWithKudos = User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-01',
            'role' => 'contributor',
        ]);
        $userNoInteraction = User::factory()->create([
            'created_at' => '2026-04-05',
            'email_verified_at' => '2026-04-05',
            'role' => 'contributor',
        ]);

        Like::factory()->kudos()->create([
            'user_id' => $userWithKudos->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => '2026-04-10',
        ]);

        $metric = new OnboardingInteraction30dRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(50, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_compute_as_of_ignores_interactions_after_date(): void
    {
        $fiche = Fiche::factory()->create();
        $user = User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-01',
            'role' => 'contributor',
        ]);
        Like::factory()->kudos()->create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => '2026-04-20',
        ]);

        $metric = new OnboardingInteraction30dRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(0, $value->current);
    }
}
