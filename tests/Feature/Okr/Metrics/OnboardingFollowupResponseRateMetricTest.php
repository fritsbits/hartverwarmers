<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\OnboardingFollowupResponseRateMetric;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFollowupResponseRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_excludes_followups_sent_after_date(): void
    {
        $fiche = Fiche::factory()->create();
        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => "download_followup_{$fiche->id}",
            'sent_at' => '2026-04-20',
        ]);

        $metric = new OnboardingFollowupResponseRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertNull($value->current);
        $this->assertTrue($value->lowData);
    }

    public function test_compute_as_of_counts_pre_date_responses_only(): void
    {
        $fiche = Fiche::factory()->create();
        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => "download_followup_{$fiche->id}",
            'sent_at' => '2026-04-01',
        ]);
        Like::factory()->kudos()->create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'created_at' => '2026-04-10',
        ]);

        $metric = new OnboardingFollowupResponseRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(100, $value->current);
    }
}
