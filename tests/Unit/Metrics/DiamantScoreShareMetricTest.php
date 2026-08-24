<?php

namespace Tests\Unit\Metrics;

use App\Metrics\DiamantScoreShareMetric;
use App\Models\Fiche;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DiamantScoreShareMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_share_of_published_fiches_reaching_the_threshold(): void
    {
        $this->publishScored([90, 85, 75, 70, 60, 55, 40, 30, 20, 10]);

        $value = (new DiamantScoreShareMetric)->compute('month');

        $this->assertInstanceOf(MetricValue::class, $value);
        $this->assertSame(40, $value->current);
        $this->assertSame('%', $value->unit);
        $this->assertFalse($value->lowData);
    }

    public function test_threshold_is_inclusive(): void
    {
        $this->publishScored([70, 69]);

        $this->assertSame(50, (new DiamantScoreShareMetric)->compute('month')->current);
    }

    public function test_ignores_unpublished_and_unscored_fiches(): void
    {
        $this->publishScored([90, 30]);
        Fiche::factory()->withQualityScore(10)->create();
        Fiche::factory()->published()->create();

        $this->assertSame(50, (new DiamantScoreShareMetric)->compute('month')->current);
    }

    public function test_returns_null_current_and_lowdata_true_when_no_scored_fiches(): void
    {
        Fiche::factory()->published()->create();

        $value = (new DiamantScoreShareMetric)->compute('month');

        $this->assertNull($value->current);
        $this->assertTrue($value->lowData);
    }

    public function test_flags_low_data_below_ten_scored_fiches(): void
    {
        $this->publishScored([90, 80, 70, 60, 50, 40, 30, 20, 10]);

        $value = (new DiamantScoreShareMetric)->compute('month');

        $this->assertSame(33, $value->current);
        $this->assertTrue($value->lowData);
    }

    public function test_exposes_no_previous_value_so_the_kr_card_shows_no_delta(): void
    {
        $this->publishScored([90, 30]);

        $this->assertNull((new DiamantScoreShareMetric)->compute('month')->previous);
    }

    public function test_compute_as_of_only_counts_fiches_that_existed_then(): void
    {
        $this->publishScored([90, 80], createdAt: now()->subMonths(2));
        $this->publishScored([10, 20], createdAt: now()->subDay());

        $metric = new DiamantScoreShareMetric;

        $this->assertSame(100, $metric->computeAsOf(CarbonImmutable::now()->subMonth())->current);
        $this->assertSame(50, $metric->computeAsOf(CarbonImmutable::now())->current);
    }

    public function test_compute_as_of_returns_low_data_before_any_fiche_existed(): void
    {
        $this->publishScored([90, 80]);

        $value = (new DiamantScoreShareMetric)->computeAsOf(CarbonImmutable::now()->subYear());

        $this->assertNull($value->current);
        $this->assertTrue($value->lowData);
    }

    /** @param  array<int, int>  $scores */
    private function publishScored(array $scores, ?Carbon $createdAt = null): void
    {
        foreach ($scores as $score) {
            Fiche::factory()
                ->published()
                ->withQualityScore($score)
                ->create($createdAt ? ['created_at' => $createdAt] : []);
        }
    }
}
