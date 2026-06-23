<?php

namespace Tests\Unit\Metrics;

use App\Metrics\ReactivationRateMetric;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactivationRateMetricTest extends TestCase
{
    use RefreshDatabase;

    private function key(): string
    {
        return config('newsletter.reactivation_mail_key');
    }

    private function logSend(User $user, $sentAt): void
    {
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => $this->key(),
            'sent_at' => $sentAt,
        ]);
    }

    public function test_empty_state_no_sends(): void
    {
        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertEquals(0, $result->current);
        $this->assertEquals('%', $result->unit);
        $this->assertFalse($result->lowData);
    }

    public function test_reactivated_user_counts(): void
    {
        // Slaper: kreeg de mail 5 dagen geleden, kwam 2 dagen geleden terug.
        $u = User::factory()->create(['last_visited_at' => now()->subDays(2)]);
        $this->logSend($u, now()->subDays(5));

        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertEquals(100, $result->current);
        $this->assertTrue($result->lowData); // 1 send < 50
    }

    public function test_never_visited_not_counted(): void
    {
        $u = User::factory()->create(['last_visited_at' => null]);
        $this->logSend($u, now()->subDays(5));

        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertEquals(0, $result->current);
    }

    public function test_visit_before_send_not_counted(): void
    {
        $u = User::factory()->create(['last_visited_at' => now()->subDays(10)]);
        $this->logSend($u, now()->subDays(5));

        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertEquals(0, $result->current);
    }

    public function test_mixed_ratio_rounds(): void
    {
        // 1 van 3 gereactiveerd -> 33%
        $a = User::factory()->create(['last_visited_at' => now()->subDays(2)]);
        $b = User::factory()->create(['last_visited_at' => null]);
        $c = User::factory()->create(['last_visited_at' => now()->subDays(10)]);
        $this->logSend($a, now()->subDays(5));
        $this->logSend($b, now()->subDays(5));
        $this->logSend($c, now()->subDays(5));

        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertEquals(33, $result->current);
        $this->assertTrue($result->lowData); // 3 < 50
    }

    public function test_lowdata_clears_at_fifty_sends(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $u = User::factory()->create(['last_visited_at' => null]);
            $this->logSend($u, now()->subDays(2));
        }

        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertFalse($result->lowData); // 50 is not < 50
    }

    public function test_includes_imported_contacts(): void
    {
        // De campagne mikt net op @import-contacten; die mogen niet uitgesloten worden.
        $u = User::factory()->create([
            'email' => 'iemand@import.hartverwarmers.be',
            'last_visited_at' => now()->subDays(1),
        ]);
        $this->logSend($u, now()->subDays(3));

        $result = (new ReactivationRateMetric)->compute('month');

        $this->assertEquals(100, $result->current);
    }

    public function test_range_week_excludes_older_sends(): void
    {
        $recent = User::factory()->create(['last_visited_at' => now()->subDay()]);
        $this->logSend($recent, now()->subDays(2));

        $old = User::factory()->create(['last_visited_at' => now()->subDay()]);
        $this->logSend($old, now()->subDays(10));

        $result = (new ReactivationRateMetric)->compute('week');

        $this->assertEquals(100, $result->current); // enkel de recente send telt
    }

    public function test_compute_as_of_is_cumulative_up_to_date(): void
    {
        $u = User::factory()->create(['last_visited_at' => now()->subDays(40)]);
        $this->logSend($u, now()->subDays(45));

        $result = (new ReactivationRateMetric)->computeAsOf(CarbonImmutable::now());

        $this->assertEquals(100, $result->current);
    }
}
