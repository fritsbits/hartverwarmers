<?php

namespace Tests\Unit\Metrics;

use App\Metrics\OnboardingInteraction30dRateMetric;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingInteraction30dRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_kudos_within_30_days_is_counted(): void
    {
        $verifiedAt = now()->subDays(20);
        $user = User::factory()->create([
            'created_at' => now()->subDays(20),
            'email_verified_at' => $verifiedAt,
        ]);
        $fiche = Fiche::factory()->create();
        Like::factory()->kudos()->create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => $verifiedAt->copy()->addDays(5),
        ]);

        $value = (new OnboardingInteraction30dRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_user_with_comment_within_30_days_is_counted(): void
    {
        $verifiedAt = now()->subDays(20);
        $user = User::factory()->create([
            'created_at' => now()->subDays(20),
            'email_verified_at' => $verifiedAt,
        ]);
        $fiche = Fiche::factory()->create();
        Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'created_at' => $verifiedAt->copy()->addDays(5),
        ]);

        $value = (new OnboardingInteraction30dRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
    }

    public function test_user_with_both_kudos_and_comment_is_counted_once(): void
    {
        $verifiedAt = now()->subDays(20);
        $user = User::factory()->create([
            'created_at' => now()->subDays(20),
            'email_verified_at' => $verifiedAt,
        ]);
        // Second verified user with NO interactions
        User::factory()->create([
            'created_at' => now()->subDays(20),
            'email_verified_at' => now()->subDays(20),
        ]);
        $fiche = Fiche::factory()->create();

        Like::factory()->kudos()->create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => $verifiedAt->copy()->addDays(2),
        ]);
        Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'created_at' => $verifiedAt->copy()->addDays(3),
        ]);

        $value = (new OnboardingInteraction30dRateMetric)->compute('month');

        // 1 unique interactor out of 2 cohort users = 50%
        $this->assertSame(50, $value->current);
    }

    public function test_action_after_30_days_is_not_counted(): void
    {
        $verifiedAt = now()->subDays(40);
        $user = User::factory()->create([
            'created_at' => now()->subDays(40),
            'email_verified_at' => $verifiedAt,
        ]);
        $fiche = Fiche::factory()->create();
        // Kudos > 30 days after verification
        Like::factory()->kudos()->create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => $verifiedAt->copy()->addDays(35),
        ]);

        $value = (new OnboardingInteraction30dRateMetric)->compute('quarter');

        $this->assertSame(0, $value->current);
    }

    public function test_returns_zero_when_cohort_is_empty(): void
    {
        $value = (new OnboardingInteraction30dRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
    }
}
