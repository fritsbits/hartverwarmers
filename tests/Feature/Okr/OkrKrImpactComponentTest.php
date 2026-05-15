<?php

namespace Tests\Feature\Okr;

use App\Services\Okr\InitiativeKrImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrKrImpactComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_baseline_arrow_current_delta(): void
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
            sparkline: [
                ['label' => 'W12', 'value' => 8],
                ['label' => 'W13', 'value' => 9],
                ['label' => 'W14', 'value' => 10],
                ['label' => 'W15', 'value' => 11],
                ['label' => 'W16', 'value' => 12],
                ['label' => 'W17', 'value' => 14],
            ],
            markerIndex: 4,
        );

        $rendered = $this->blade('<x-okr-kr-impact :impact="$impact" />', ['impact' => $impact]);

        $rendered->assertSee('Aanmeldingen');
        $rendered->assertSee('10');
        $rendered->assertSee('14');
        $rendered->assertSee('+4');
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
