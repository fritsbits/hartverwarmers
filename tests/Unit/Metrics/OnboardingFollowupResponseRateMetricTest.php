<?php

namespace Tests\Unit\Metrics;

use App\Metrics\OnboardingFollowupResponseRateMetric;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFollowupResponseRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_current_when_no_follow_up_emails_sent(): void
    {
        $value = (new OnboardingFollowupResponseRateMetric)->compute('month');

        $this->assertNull($value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_user_who_kudoses_fiche_after_followup_send_counts_as_responded(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => "download_followup_{$fiche->id}",
            'sent_at' => now()->subDays(5),
        ]);

        Like::factory()->create([
            'user_id' => $user->id,
            'type' => 'kudos',
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => now()->subDays(2),
        ]);

        $value = (new OnboardingFollowupResponseRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
    }

    public function test_user_who_kudoses_before_followup_send_does_not_count(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        // Kudos BEFORE the follow-up email was sent
        Like::factory()->create([
            'user_id' => $user->id,
            'type' => 'kudos',
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => now()->subDays(10),
        ]);

        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => "download_followup_{$fiche->id}",
            'sent_at' => now()->subDays(5),
        ]);

        $value = (new OnboardingFollowupResponseRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
    }

    public function test_partial_response_rate_two_emails_one_response(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $ficheA = Fiche::factory()->for(User::factory(), 'user')->create();
        $ficheB = Fiche::factory()->for(User::factory(), 'user')->create();

        OnboardingEmailLog::create([
            'user_id' => $userA->id,
            'mail_key' => "download_followup_{$ficheA->id}",
            'sent_at' => now()->subDays(5),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $userB->id,
            'mail_key' => "download_followup_{$ficheB->id}",
            'sent_at' => now()->subDays(5),
        ]);

        // Only userA responds
        Like::factory()->create([
            'user_id' => $userA->id,
            'type' => 'kudos',
            'likeable_type' => Fiche::class,
            'likeable_id' => $ficheA->id,
            'created_at' => now()->subDays(2),
        ]);

        $value = (new OnboardingFollowupResponseRateMetric)->compute('month');

        $this->assertSame(50, $value->current);
    }

    public function test_comment_also_counts_as_response(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for(User::factory(), 'user')->create();

        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => "download_followup_{$fiche->id}",
            'sent_at' => now()->subDays(5),
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'created_at' => now()->subDays(2),
        ]);

        $value = (new OnboardingFollowupResponseRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
    }
}
