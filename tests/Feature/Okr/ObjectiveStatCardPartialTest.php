<?php

namespace Tests\Feature\Okr;

use App\Services\Okr\MetricValue;
use App\Services\Okr\ObjectiveStat;
use Tests\TestCase;

class ObjectiveStatCardPartialTest extends TestCase
{
    private function render(ObjectiveStat $stat, string $range = 'month'): string
    {
        return view('admin.partials.objective-stat-card', ['stat' => $stat, 'range' => $range])->render();
    }

    public function test_renders_title_value_delta_and_deep_link_with_range(): void
    {
        $stat = new ObjectiveStat(
            title: 'Activatie',
            slug: 'onboarding',
            value: new MetricValue(current: 42, previous: 30, unit: ''),
            series: [['label' => '2026-05-01', 'value' => 40], ['label' => '2026-05-08', 'value' => 42]],
        );

        $html = $this->render($stat, 'quarter');

        $this->assertStringContainsString('Activatie', $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringContainsString('+12', $html);
        $this->assertStringContainsString('?tab=onboarding&amp;range=quarter', $html);
        $this->assertStringContainsString('data-testid="objective-stat-sparkline"', $html);
        $this->assertStringContainsString('aria-label="Activatie"', $html);
    }

    public function test_omits_chart_when_series_empty_and_shows_dash_for_null(): void
    {
        $stat = new ObjectiveStat(
            title: 'Fichekwaliteit',
            slug: 'presentatiekwaliteit',
            value: new MetricValue(current: null),
            series: [],
        );

        $html = $this->render($stat);

        $this->assertStringContainsString('Fichekwaliteit', $html);
        $this->assertStringContainsString('—', $html);
        $this->assertStringNotContainsString('data-testid="objective-stat-sparkline"', $html);
    }
}
