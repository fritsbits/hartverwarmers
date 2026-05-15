<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrBedankjesTabTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_renders_empty_state_and_keeps_tab_level_context_when_no_initiative(): void
    {
        $obj = Objective::factory()->create(['slug' => 'bedankjes', 'title' => 'Interactie']);
        KeyResult::factory()->create([
            'objective_id' => $obj->id,
            'metric_key' => 'thank_rate',
            'label' => 'Bedankratio',
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=bedankjes');

        $response->assertOk();
        $response->assertSee('Bedankratio');             // KR at top
        $response->assertSee('Nog geen initiatief');      // empty-state where initiative sections would be
        $response->assertSee('Hoe bedanken mensen');      // tab-level context PRESERVED
    }

    public function test_renders_initiative_section_when_one_exists(): void
    {
        $obj = Objective::factory()->create(['slug' => 'bedankjes', 'title' => 'Interactie']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'thank_rate', 'label' => 'Bedankratio']);
        Initiative::create([
            'objective_id' => $obj->id,
            'slug' => 'bedank-flow',
            'label' => 'Bedank-flow',
            'status' => 'in_progress',
            'started_at' => '2026-04-01',
            'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=bedankjes');

        $response->assertOk();
        $response->assertSee('id="initiative-bedank-flow"', escape: false);
        $response->assertSee('Impact op dit doel');
        $response->assertSee('Hoe bedanken mensen'); // tab-level context still present even WITH an initiative
    }
}
