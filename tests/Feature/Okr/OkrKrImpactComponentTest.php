<?php

namespace Tests\Feature\Okr;

use App\Services\Okr\InitiativeKrImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrKrImpactComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_before_after_and_plain_language_change_for_count(): void
    {
        $impact = new InitiativeKrImpact(
            krId: 1,
            krLabel: 'Aanmeldingen',
            baselineValue: 10,
            currentValue: 14,
            delta: 4,
            unit: '',
            baselineLowData: false,
            currentLowData: false,
            sparkline: [],
            markerIndex: 0,
        );

        $rendered = $this->blade('<x-okr-kr-impact :impact="$impact" />', ['impact' => $impact]);

        $rendered->assertSee('Aanmeldingen');
        $rendered->assertSee('bij de start');
        $rendered->assertSee('10');
        $rendered->assertSee('14');
        // Counts read as "meer/minder", never as percentage points.
        $rendered->assertSee('4 meer');
        $rendered->assertDontSee('procentpunt');
        $rendered->assertDontSee('pp');
    }

    public function test_trend_splits_before_and_since_launch_with_legend(): void
    {
        $impact = new InitiativeKrImpact(
            krId: 1,
            krLabel: 'Slapers terug actief',
            baselineValue: 0,
            currentValue: 8,
            delta: 8,
            unit: '%',
            baselineLowData: false,
            currentLowData: false,
            sparkline: [
                ['label' => '25 May', 'value' => 0],
                ['label' => '01 Jun', 'value' => 0],
                ['label' => '08 Jun', 'value' => 0],
                ['label' => '15 Jun', 'value' => 0],
                ['label' => '22 Jun', 'value' => 8],
            ],
            markerIndex: 4,
        );

        $rendered = $this->blade('<x-okr-kr-impact :impact="$impact" />', ['impact' => $impact]);

        // The chart legend names the two phases so the colour split is readable.
        $rendered->assertSee('Vóór de start');
        $rendered->assertSee('Sinds de start');
        // The weekly axis labels still render.
        $rendered->assertSee('22 Jun');
        $rendered->assertSee('8 procentpunt hoger');
    }

    public function test_percentage_change_is_labelled_procentpunt_not_pp(): void
    {
        $impact = new InitiativeKrImpact(
            krId: 1,
            krLabel: 'Slapers terug actief',
            baselineValue: 0,
            currentValue: 8,
            delta: 8,
            unit: '%',
            baselineLowData: false,
            currentLowData: false,
            sparkline: [],
            markerIndex: 0,
        );

        $rendered = $this->blade('<x-okr-kr-impact :impact="$impact" />', ['impact' => $impact]);

        $rendered->assertSee('0%');
        $rendered->assertSee('8%');
        $rendered->assertSee('8 procentpunt hoger');
        $rendered->assertDontSee('pp');
    }

    public function test_renders_no_difference_message_when_delta_zero(): void
    {
        $impact = new InitiativeKrImpact(
            krId: 1,
            krLabel: 'Bedankratio',
            baselineValue: 20,
            currentValue: 20,
            delta: 0,
            unit: '%',
            baselineLowData: false,
            currentLowData: false,
            sparkline: [],
            markerIndex: 0,
        );

        $rendered = $this->blade('<x-okr-kr-impact :impact="$impact" />', ['impact' => $impact]);

        $rendered->assertSee('nog geen verschil sinds de start');
    }

    public function test_renders_low_data_badge_when_baseline_or_current_low(): void
    {
        $impact = new InitiativeKrImpact(
            krId: 1,
            krLabel: 'X',
            baselineValue: null,
            currentValue: null,
            delta: null,
            unit: '',
            baselineLowData: true,
            currentLowData: false,
            sparkline: [],
            markerIndex: 0,
        );

        $rendered = $this->blade('<x-okr-kr-impact :impact="$impact" />', ['impact' => $impact]);
        $rendered->assertSee('Te weinig data');
    }
}
