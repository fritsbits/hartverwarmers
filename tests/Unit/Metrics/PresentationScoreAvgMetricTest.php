<?php

namespace Tests\Unit\Metrics;

use App\Metrics\PresentationScoreAvgMetric;
use App\Models\Fiche;
use App\Services\Okr\MetricValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationScoreAvgMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_global_average_score_across_published_fiches(): void
    {
        Fiche::factory()->published()->withPresentationScore(80)->create();
        Fiche::factory()->published()->withPresentationScore(60)->create();
        Fiche::factory()->published()->withPresentationScore(40)->create();

        $value = (new PresentationScoreAvgMetric)->compute('month');

        $this->assertInstanceOf(MetricValue::class, $value);
        $this->assertSame(60, $value->current);
        $this->assertSame('', $value->unit);
        $this->assertFalse($value->lowData);
    }

    public function test_ignores_unpublished_and_unscored_fiches(): void
    {
        Fiche::factory()->published()->withPresentationScore(90)->create();
        Fiche::factory()->withPresentationScore(10)->create();
        Fiche::factory()->published()->create();

        $value = (new PresentationScoreAvgMetric)->compute('month');

        $this->assertSame(90, $value->current);
    }

    public function test_returns_null_current_and_lowdata_true_when_no_scored_fiches(): void
    {
        $value = (new PresentationScoreAvgMetric)->compute('month');

        $this->assertNull($value->current);
        $this->assertTrue($value->lowData);
    }
}
