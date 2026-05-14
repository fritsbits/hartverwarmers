<?php

namespace Tests\Unit\Metrics;

use App\Metrics\NewsletterActivationRateMetric;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterActivationRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_happy_path_single_activated_user(): void
    {
        // User visited 3 days after receiving — counts as activated
        $u1 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(2),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u1->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(5),
        ]);

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('month');

        $this->assertEquals(100, $result->current);
        $this->assertEquals('%', $result->unit);
        $this->assertTrue($result->lowData); // 1 send is < 5
    }

    public function test_visit_outside_seven_day_window_not_counted(): void
    {
        // Send 20 days ago, visited 5 days ago (15 days after send) — outside 7d window
        $u = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(5),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(20),
        ]);

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('month');

        $this->assertEquals(0, $result->current);
        $this->assertTrue($result->lowData); // 1 send is < 5
    }

    public function test_empty_state_no_sends(): void
    {
        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('month');

        $this->assertEquals(0, $result->current);
        $this->assertFalse($result->lowData);
    }

    public function test_lowdata_flag_under_five_sends(): void
    {
        // Create 3 newsletter sends (all without activated users)
        for ($i = 0; $i < 3; $i++) {
            $u = User::factory()->create(['role' => 'contributor']);
            OnboardingEmailLog::create([
                'user_id' => $u->id,
                'mail_key' => 'newsletter-cycle-1',
                'sent_at' => now()->subDays(2),
            ]);
        }

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('month');

        $this->assertTrue($result->lowData);
    }

    public function test_complex_scenario_mixed_activated(): void
    {
        // User visited 3 days after receiving — counts as activated
        $u1 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(2),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u1->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(5),
        ]);

        // User visited but BEFORE the send — not activated
        $u2 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(10),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u2->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(5),
        ]);

        // User never visited — not activated
        $u3 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => null,
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u3->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(5),
        ]);

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('month');

        $this->assertEquals(3, 1 + 1 + 1); // 3 sends total
        $this->assertEquals(33, $result->current); // 1/3 * 100 = 33
        $this->assertTrue($result->lowData); // 3 sends, and 3 > 0 && 3 < 5
    }

    public function test_range_week(): void
    {
        // Create a send within the last 7 days with an activation
        $u = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(1),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(2),
        ]);

        // Create a send outside the range (older than 7 days)
        $u2 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(1),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u2->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(10),
        ]);

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('week');

        $this->assertEquals(100, $result->current);
    }

    public function test_range_quarter(): void
    {
        // Create a send within the last 90 days with an activation
        $u = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(45),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(50),
        ]);

        // Create a send outside the range (older than 90 days)
        $u2 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(100),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $u2->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(110),
        ]);

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('quarter');

        $this->assertEquals(100, $result->current);
    }

    public function test_excludes_admin_users(): void
    {
        // Admin user should be excluded
        $admin = User::factory()->create([
            'role' => 'admin',
            'last_visited_at' => now()->subDays(2),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $admin->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(5),
        ]);

        // Contributor user should be included
        $contributor = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(2),
        ]);
        OnboardingEmailLog::create([
            'user_id' => $contributor->id,
            'mail_key' => 'newsletter-cycle-1',
            'sent_at' => now()->subDays(5),
        ]);

        $metric = new NewsletterActivationRateMetric;
        $result = $metric->compute('month');

        $this->assertEquals(100, $result->current); // Only 1 send (contributor) counted, and it's activated -> 100%
    }
}
