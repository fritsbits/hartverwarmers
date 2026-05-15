<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Services\Okr\InitiativeImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeCardPartialTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_renders_initiative_title_objective_chip_and_deep_link(): void
    {
        $objective = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Onboarding']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
            'label' => 'Aanmeldingen',
        ]);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'onboarding-emails',
            'label' => 'Onboarding-e-mails',
            'description' => 'Drip campagne',
            'status' => 'in_progress',
            'started_at' => '2026-04-02',
            'position' => 1,
        ]);

        $summary = app(InitiativeImpact::class)->forInitiative($initiative->fresh());

        $html = view('admin.partials.initiative-card', [
            'initiative' => $summary->initiative,
            'summary' => $summary,
        ])->render();

        $this->assertStringContainsString('Onboarding-e-mails', $html);
        $this->assertStringContainsString('Drip campagne', $html);
        $this->assertStringContainsString('Onboarding', $html); // objective chip
        $this->assertStringContainsString('?tab=onboarding&amp;init=onboarding-emails', $html);
        $this->assertStringContainsString('Aanmeldingen', $html); // KR impact rendered
        $this->assertStringContainsString('Live sinds', $html);
    }
}
