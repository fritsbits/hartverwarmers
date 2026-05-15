<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_overview_lists_started_initiatives_newest_first(): void
    {
        $obj = Objective::factory()->create();
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'onboarding_signup_count']);

        Initiative::create([
            'objective_id' => $obj->id, 'slug' => 'oudste', 'label' => 'Oudste Initiatief',
            'status' => 'in_progress', 'started_at' => '2026-01-01', 'position' => 1,
        ]);
        Initiative::create([
            'objective_id' => $obj->id, 'slug' => 'nieuwste', 'label' => 'Nieuwste Initiatief',
            'status' => 'in_progress', 'started_at' => '2026-04-15', 'position' => 2,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=overzicht');

        $response->assertOk();
        $response->assertSeeInOrder(['Nieuwste Initiatief', 'Oudste Initiatief']);
    }

    public function test_overview_hides_started_initiatives_kr_block_for_planned(): void
    {
        $obj = Objective::factory()->create();
        Initiative::create([
            'objective_id' => $obj->id, 'slug' => 'planned', 'label' => 'Gepland Iets',
            'status' => 'soon', 'started_at' => null, 'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=overzicht');

        $response->assertOk();
        $response->assertSee('Gepland Iets');
        $response->assertDontSee('Impact op'); // planned items have no KR-impact block
    }

    public function test_overview_card_links_to_objective_tab_with_init_anchor(): void
    {
        $obj = Objective::factory()->create(['slug' => 'onboarding']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'onboarding_signup_count']);
        Initiative::create([
            'objective_id' => $obj->id, 'slug' => 'foo', 'label' => 'Foo',
            'status' => 'in_progress', 'started_at' => '2026-04-15', 'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=overzicht');

        // Blade {{ }} escapes & to &amp; in the href. assertSee escapes its needle by
        // default, so passing a raw & here matches the rendered &amp;. Do NOT use escape:false with a raw &.
        $response->assertSee('?tab=onboarding&init=foo');
    }
}
