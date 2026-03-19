<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiamantjesCuratorSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_diamond_redirects_to_custom_url_when_redirect_param_given(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->actingAs($admin)->post(
            route('fiches.toggleDiamond', [$initiative, $fiche]),
            ['_redirect' => route('diamantjes.index')]
        );

        $response->assertRedirect(route('diamantjes.index'));
    }

    public function test_toggle_diamond_redirects_to_fiche_show_by_default(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->actingAs($admin)->post(
            route('fiches.toggleDiamond', [$initiative, $fiche])
        );

        $response->assertRedirect(route('fiches.show', [$initiative, $fiche]));
    }
}
