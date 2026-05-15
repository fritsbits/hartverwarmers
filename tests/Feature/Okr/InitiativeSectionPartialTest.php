<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Services\Okr\InitiativeImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeSectionPartialTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_renders_anchor_header_impact_and_context(): void
    {
        $objective = Objective::factory()->create(['slug' => 'presentatiekwaliteit', 'title' => 'Fichekwaliteit']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'presentation_score_avg',
            'label' => 'Gemiddelde presentatiescore',
        ]);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'ai-suggesties',
            'label' => 'AI-suggesties',
            'description' => 'AI helpt fiches verbeteren',
            'status' => 'in_progress',
            'started_at' => '2026-03-17',
            'position' => 1,
        ]);

        $summary = app(InitiativeImpact::class)->forInitiative($initiative->fresh());

        $html = view('admin.partials.initiative-section', [
            'initiative' => $summary->initiative,
            'summary' => $summary,
            'contextView' => 'testing.okr-stub-context',
        ])->render();

        $this->assertStringContainsString('id="initiative-ai-suggesties"', $html);
        $this->assertStringContainsString('AI-suggesties', $html);
        $this->assertStringContainsString('AI helpt fiches verbeteren', $html);
        $this->assertStringContainsString('Impact op dit doel', $html);
        $this->assertStringContainsString('Gemiddelde presentatiescore', $html);
        $this->assertStringContainsString('Live sinds', $html);
    }

    public function test_section_without_context_view_renders_no_context_block(): void
    {
        $objective = Objective::factory()->create(['slug' => 'bedankjes', 'title' => 'Interactie']);
        KeyResult::factory()->create(['objective_id' => $objective->id, 'metric_key' => 'thank_rate', 'label' => 'Bedankratio']);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'iets',
            'label' => 'Iets',
            'status' => 'in_progress',
            'started_at' => '2026-04-01',
            'position' => 1,
        ]);
        $summary = app(InitiativeImpact::class)->forInitiative($initiative->fresh());

        $html = view('admin.partials.initiative-section', [
            'initiative' => $summary->initiative,
            'summary' => $summary,
            'contextView' => null,
        ])->render();

        $this->assertStringContainsString('id="initiative-iets"', $html);
        $this->assertStringNotContainsString('Context', $html);
    }

    public function test_weeks_live_renders_as_whole_number_not_float(): void
    {
        $objective = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Activatie']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
            'label' => 'Aanmeldingen',
        ]);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'recent',
            'label' => 'Recent',
            'status' => 'in_progress',
            'started_at' => now()->subDays(3)->toDateString(),
            'position' => 1,
        ]);
        $summary = app(InitiativeImpact::class)->forInitiative($initiative->fresh());

        $html = view('admin.partials.initiative-section', ['initiative' => $summary->initiative, 'summary' => $summary, 'contextView' => null])->render();

        // 3 days ago → 0 whole weeks, must NOT contain a decimal point in the weeks label
        $this->assertStringContainsString('0 weken', $html);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+\s*we(ek|ken)/', $html);
    }
}
