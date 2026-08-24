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

    public function test_tab_shows_the_score_distribution_with_the_threshold_gap(): void
    {
        $this->seed(OkrSeeder::class);
        // 2 sterk van 10 = 20%; de 3 fiches in 60-69 zouden dat op 50% brengen.
        $this->publishScored([72, 82, 62, 65, 69, 50, 40, 30, 20, 10]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => 'inhoudelijke-kwaliteit']));

        $response->assertOk();
        $response->assertSee('Waar de bibliotheek staat');
        $response->assertSee('3 fiches zitten');
        $response->assertSee('van 20% naar 50%');
    }

    public function test_cohort_chart_reports_the_month_a_fiche_was_made(): void
    {
        $this->seed(OkrSeeder::class);
        Fiche::factory()->published()->withQualityScore(80)->create(['created_at' => now()->subMonth()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => 'inhoudelijke-kwaliteit']));

        $response->assertOk();
        $response->assertSee('Laatste lichting');
        $response->assertSee(now()->subMonth()->isoFormat('MMM YY').': 1 fiche, gemiddeld 80');
    }

    public function test_cohort_chart_says_so_when_no_fiches_were_made_in_the_period(): void
    {
        $this->seed(OkrSeeder::class);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => 'inhoudelijke-kwaliteit', 'range' => 'month']));

        $response->assertOk();
        $response->assertSee('Geen fiches gemaakt in deze periode.');
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
