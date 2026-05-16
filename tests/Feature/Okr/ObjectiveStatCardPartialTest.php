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

    public function test_renders_title_value_delta_deep_link_and_goal_progress(): void
    {
        $stat = new ObjectiveStat(
            title: 'Interactie',
            slug: 'bedankjes',
            value: new MetricValue(current: 42, previous: 30, unit: '%'),
            target: 50,
            metricKey: 'thank_rate',
        );

        $html = $this->render($stat, 'quarter');

        $this->assertStringContainsString('Interactie', $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringContainsString('+12', $html);
        $this->assertStringContainsString('?tab=bedankjes&amp;range=quarter', $html);
        $this->assertStringContainsString('aria-label="Interactie"', $html);
        // Decorative sparkline replaced by a meaningful goal-progress bar.
        $this->assertStringNotContainsString('data-testid="objective-stat-sparkline"', $html);
        $this->assertStringContainsString('data-testid="objective-stat-progress"', $html);
        $this->assertStringContainsString('Doel', $html);
    }

    public function test_no_progress_bar_without_target_and_no_sparkline(): void
    {
        $stat = new ObjectiveStat(
            title: 'Activatie',
            slug: 'onboarding',
            value: new MetricValue(current: 4849, unit: ''),
            target: null,
            metricKey: 'onboarding_signup_count',
        );

        $html = $this->render($stat);

        $this->assertStringContainsString('Nog geen doel ingesteld', $html);
        $this->assertStringNotContainsString('data-testid="objective-stat-progress"', $html);
        $this->assertStringNotContainsString('data-testid="objective-stat-sparkline"', $html);
    }

    public function test_shows_not_measured_and_dash_for_null_value(): void
    {
        $stat = new ObjectiveStat(
            title: 'Fichekwaliteit',
            slug: 'presentatiekwaliteit',
            value: new MetricValue(current: null),
            target: 50,
            metricKey: 'presentation_score_avg',
        );

        $html = $this->render($stat);

        $this->assertStringContainsString('Fichekwaliteit', $html);
        $this->assertStringContainsString('—', $html);
        $this->assertStringContainsString('Nog niet gemeten', $html);
        $this->assertStringNotContainsString('data-testid="objective-stat-sparkline"', $html);
        $this->assertStringNotContainsString('data-testid="objective-stat-progress"', $html);
    }
}
