<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\NewsletterActivationRateMetric;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterActivationRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_excludes_sends_after_date(): void
    {
        $user = User::factory()->create(['last_visited_at' => '2026-04-25']);
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => '2026-04-20',
        ]);

        $metric = new NewsletterActivationRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(0, $value->current);
    }

    public function test_compute_as_of_counts_activation_when_visit_within_7d_of_send_and_before_date(): void
    {
        $user = User::factory()->create(['last_visited_at' => '2026-04-05']);
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => '2026-04-01',
        ]);

        $metric = new NewsletterActivationRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(100, $value->current);
    }
}
