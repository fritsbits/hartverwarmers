<?php

namespace Tests\Feature\Okr;

use App\Models\Fiche;
use App\Models\Okr\KeyResult;
use App\Models\User;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInhoudelijkeKwaliteitTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_tab_renders_the_kr_with_its_share_and_counts(): void
    {
        $this->seed(OkrSeeder::class);
        $this->publishScored([90, 80, 75, 70, 60, 50, 40, 30, 20, 10]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => 'inhoudelijke-kwaliteit']));

        $response->assertOk();
        $response->assertSee('Inhoudelijke kwaliteit');
        $response->assertSee('Fiches met sterke diamantscore');
        $response->assertSee('40%');
        $response->assertSee('van de 10 beoordeelde fiches halen 70+');
    }

    public function test_tab_shows_the_target_once_it_is_set(): void
    {
        $this->seed(OkrSeeder::class);
        KeyResult::where('metric_key', 'diamant_score_share')->update(['target_value' => 35]);
        $this->publishScored([90, 30]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => 'inhoudelijke-kwaliteit']));

        $response->assertOk();
        $response->assertSee('35%');
    }

    public function test_tab_shows_empty_initiative_state(): void
    {
        $this->seed(OkrSeeder::class);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => 'inhoudelijke-kwaliteit']));

        $response->assertOk();
        $response->assertSee('Nog geen initiatief gestart voor dit doel.');
    }

    public function test_tab_button_appears_in_navigation(): void
    {
        $this->seed(OkrSeeder::class);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Inhoudelijke kwaliteit');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @param  array<int, int>  $scores */
    private function publishScored(array $scores): void
    {
        foreach ($scores as $score) {
            Fiche::factory()->published()->withQualityScore($score)->create();
        }
    }
}
