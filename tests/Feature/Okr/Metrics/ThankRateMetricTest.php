<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\ThankRateMetric;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThankRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_window_excludes_downloads_after_date(): void
    {
        $fiche = Fiche::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        UserInteraction::create([
            'user_id' => $userA->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => '2026-04-01',
        ]);

        // This download is after the cutoff date and must be excluded
        UserInteraction::create([
            'user_id' => $userB->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => '2026-04-20',
        ]);

        $metric = new ThankRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(0, $value->current);
    }

    public function test_compute_as_of_counts_kudos_within_window(): void
    {
        $fiche = Fiche::factory()->create();
        $user = User::factory()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => '2026-04-01',
        ]);

        Like::factory()->create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
            'created_at' => '2026-04-05',
        ]);

        $metric = new ThankRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(100, $value->current);
    }
}
