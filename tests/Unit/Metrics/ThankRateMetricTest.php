<?php

namespace Tests\Unit\Metrics;

use App\Metrics\ThankRateMetric;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThankRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_download_kudos_counts_as_thanked(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);

        Like::factory()->create([
            'user_id' => $user->id,
            'type' => 'kudos',
            'count' => 1,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => now()->subDays(3),
        ]);

        $value = (new ThankRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_pre_download_kudos_does_not_count(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        Like::factory()->create([
            'user_id' => $user->id,
            'type' => 'kudos',
            'count' => 1,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => now()->subDays(10),
        ]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);

        $value = (new ThankRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
    }

    public function test_post_download_comment_counts_as_thanked(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'created_at' => now()->subDays(3),
        ]);

        $value = (new ThankRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
    }

    public function test_partial_rate_one_of_two_downloads_thanked(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        UserInteraction::create([
            'user_id' => $userA->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        UserInteraction::create([
            'user_id' => $userB->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);

        Like::factory()->create([
            'user_id' => $userA->id,
            'type' => 'kudos',
            'count' => 1,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => now()->subDays(3),
        ]);

        $value = (new ThankRateMetric)->compute('month');

        $this->assertSame(50, $value->current);
    }

    public function test_zero_count_kudos_does_not_count_as_thanked(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);

        // count = 0 means kudos was given then revoked
        Like::factory()->create([
            'user_id' => $user->id,
            'type' => 'kudos',
            'count' => 0,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => now()->subDays(3),
        ]);

        $value = (new ThankRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
    }

    public function test_low_data_flag_under_five_downloads(): void
    {
        $author = User::factory()->create();
        $fiche = Fiche::factory()->for($author, 'user')->create();

        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
                'created_at' => now()->subDays(5),
            ]);
        }

        $value = (new ThankRateMetric)->compute('month');

        $this->assertTrue($value->lowData);
    }

    public function test_delta_is_current_minus_previous_rate(): void
    {
        $author = User::factory()->create();
        $fiche = Fiche::factory()->for($author, 'user')->create();

        // Previous period: 5 downloads, 1 thanked → 20%
        for ($i = 0; $i < 5; $i++) {
            $u = User::factory()->create();
            UserInteraction::create([
                'user_id' => $u->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
                'created_at' => now()->subDays(45),
            ]);
            if ($i === 0) {
                Like::factory()->create([
                    'user_id' => $u->id,
                    'type' => 'kudos',
                    'count' => 1,
                    'likeable_type' => Fiche::class,
                    'likeable_id' => $fiche->id,
                    'created_at' => now()->subDays(44),
                ]);
            }
        }
        // Current period: 5 downloads, 3 thanked → 60%
        for ($i = 0; $i < 5; $i++) {
            $u = User::factory()->create();
            UserInteraction::create([
                'user_id' => $u->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
                'created_at' => now()->subDays(10),
            ]);
            if ($i < 3) {
                Like::factory()->create([
                    'user_id' => $u->id,
                    'type' => 'kudos',
                    'count' => 1,
                    'likeable_type' => Fiche::class,
                    'likeable_id' => $fiche->id,
                    'created_at' => now()->subDays(9),
                ]);
            }
        }

        $value = (new ThankRateMetric)->compute('month');

        $this->assertSame(60, $value->current);
        $this->assertSame(20, $value->previous);
        $this->assertSame(40, $value->delta());
    }
}
