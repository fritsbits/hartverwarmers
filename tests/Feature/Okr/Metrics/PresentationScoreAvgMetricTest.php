<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\PresentationScoreAvgMetric;
use App\Models\Fiche;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationScoreAvgMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_ignores_fiches_created_after_date(): void
    {
        Fiche::factory()->published()->create([
            'created_at' => '2026-03-01',
            'presentation_score' => 60,
        ]);
        Fiche::factory()->published()->create([
            'created_at' => '2026-04-15',
            'presentation_score' => 90,
        ]);

        $metric = new PresentationScoreAvgMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-01'));

        $this->assertSame(60, $value->current);
    }

    public function test_compute_as_of_flags_low_data_when_no_fiches_pre_date(): void
    {
        Fiche::factory()->published()->create([
            'created_at' => '2026-04-15',
            'presentation_score' => 90,
        ]);

        $metric = new PresentationScoreAvgMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-01'));

        $this->assertNull($value->current);
        $this->assertTrue($value->lowData);
    }
}
