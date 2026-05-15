<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrNieuwsbriefTabTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_renders_nieuwsbrief_systeem_initiative_section_with_context(): void
    {
        $obj = Objective::factory()->create(['slug' => 'nieuwsbrief', 'title' => 'Nieuwsbrief']);
        KeyResult::factory()->create([
            'objective_id' => $obj->id,
            'metric_key' => 'newsletter_activation_rate',
            'label' => 'Activatie na nieuwsbrief',
        ]);
        Initiative::create([
            'objective_id' => $obj->id,
            'slug' => 'nieuwsbrief-systeem',
            'label' => 'Nieuwsbrief-systeem',
            'status' => 'in_progress',
            'started_at' => '2026-05-13',
            'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=nieuwsbrief');

        $response->assertOk();
        $response->assertSee('id="initiative-nieuwsbrief-systeem"', escape: false);
        $response->assertSee('Nieuwsbrief-systeem');
        $response->assertSee('Impact op dit doel');
        $response->assertSee('Activatie na nieuwsbrief'); // KR at top
    }

    public function test_tab_without_initiatives_shows_empty_state(): void
    {
        $obj = Objective::factory()->create(['slug' => 'nieuwsbrief', 'title' => 'Nieuwsbrief']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'newsletter_activation_rate', 'label' => 'Activatie na nieuwsbrief']);

        $response = $this->actingAs($this->admin())->get('/admin?tab=nieuwsbrief');

        $response->assertOk();
        $response->assertSee('Nog geen initiatief');
    }
}
