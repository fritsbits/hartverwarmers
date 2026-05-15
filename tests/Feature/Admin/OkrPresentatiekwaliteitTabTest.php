<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrPresentatiekwaliteitTabTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_renders_initiative_section_with_anchor_and_impact(): void
    {
        $obj = Objective::factory()->create(['slug' => 'presentatiekwaliteit', 'title' => 'Presentatiekwaliteit']);
        KeyResult::factory()->create([
            'objective_id' => $obj->id,
            'metric_key' => 'presentation_score_avg',
            'label' => 'Gemiddelde presentatiescore',
        ]);
        Initiative::create([
            'objective_id' => $obj->id,
            'slug' => 'ai-suggesties',
            'label' => 'AI-suggesties',
            'status' => 'in_progress',
            'started_at' => '2026-03-17',
            'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=presentatiekwaliteit');

        $response->assertOk();
        $response->assertSee('id="initiative-ai-suggesties"', escape: false);
        $response->assertSee('AI-suggesties');
        $response->assertSee('Impact op dit doel');
        $response->assertSee('Gemiddelde presentatiescore'); // KR still rendered at top
    }

    public function test_tab_without_initiatives_shows_empty_state(): void
    {
        $obj = Objective::factory()->create(['slug' => 'presentatiekwaliteit', 'title' => 'Presentatiekwaliteit']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'presentation_score_avg', 'label' => 'Score']);

        $response = $this->actingAs($this->admin())->get('/admin?tab=presentatiekwaliteit');

        $response->assertOk();
        $response->assertSee('Nog geen initiatief'); // empty-state copy
    }
}
