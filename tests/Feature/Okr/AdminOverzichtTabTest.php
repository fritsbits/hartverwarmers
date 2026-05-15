<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOverzichtTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_overzicht_is_default_tab_for_admin(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // New initiative tracker: seeded initiatives appear in Gepland section with their objective titles
        $response->assertSee('AI-suggesties');
        $response->assertSee('Onboarding-e-mails');
        $response->assertSee('Nieuwsbrief-systeem');
        $response->assertSee('Gepland');
    }

    public function test_overzicht_renders_initiative_tracker(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'overzicht']));

        $response->assertOk();
        // Seeded initiatives have no started_at, so they appear in the Gepland section
        $response->assertSee('Gepland');
        $response->assertSee('AI-suggesties');
        $response->assertSee('Onboarding-e-mails');
        $response->assertSee('Nieuwsbrief-systeem');
    }

    public function test_unknown_tab_falls_back_to_overzicht(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'nonexistent']));

        $response->assertOk();
        $response->assertSee('Gepland');  // overzicht now renders initiative tracker
    }

    public function test_presentatiekwaliteit_tab_renders_kr_initiative_context(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'presentatiekwaliteit']));

        $response->assertOk();
        $response->assertSee('Gemiddelde presentatiescore');  // KR label
        $response->assertSee('AI-suggesties');                // Initiative label
        $response->assertSee('Laatste 5 fiches');             // Context heading
    }

    public function test_onboarding_tab_renders_five_funnel_krs_and_initiative(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'onboarding']));

        $response->assertOk();
        $response->assertSee('Aanmeldingen');
        $response->assertSee('E-mailverificatie');
        $response->assertSee('Return visit binnen 7 dagen');
        $response->assertSee('Interactie binnen 30 dagen');
        $response->assertSee('Follow-up reactie na download');
        $response->assertSee('Onboarding-e-mails');
    }

    public function test_onboarding_tab_renders_krs_in_funnel_order(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'onboarding']));

        $html = $response->getContent();

        $positions = [
            'Aanmeldingen' => strpos($html, 'Aanmeldingen'),
            'E-mailverificatie' => strpos($html, 'E-mailverificatie'),
            'Return visit binnen 7 dagen' => strpos($html, 'Return visit binnen 7 dagen'),
            'Interactie binnen 30 dagen' => strpos($html, 'Interactie binnen 30 dagen'),
            'Follow-up reactie na download' => strpos($html, 'Follow-up reactie na download'),
        ];

        $sorted = $positions;
        asort($sorted);

        $this->assertSame(array_keys($positions), array_keys($sorted), 'KRs should render in funnel order');
    }

    public function test_bedankjes_tab_renders_kr_and_context(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'bedankjes']));

        $response->assertOk();
        $response->assertSee('Bedankratio');
        $response->assertSee('Hoe bedanken mensen');
    }

    public function test_nieuwsbrief_tab_renders_kr_initiative_context(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'nieuwsbrief']));

        $response->assertOk();
        $response->assertSee('Activatie na nieuwsbrief');  // KR label (from seeder)
        $response->assertSee('Nieuwsbrief-systeem');       // Initiative label
        $response->assertSee('Aankomende sends');          // Context heading
    }

    public function test_kr_with_null_metric_key_renders_without_crashing(): void
    {
        // Spec allows nullable metric_key for future manual KRs. The okr-kr component must not
        // call MetricRegistry::compute() with a null key — that would throw InvalidArgumentException.
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $objective = Objective::where('slug', 'presentatiekwaliteit')->firstOrFail();
        KeyResult::create([
            'objective_id' => $objective->id,
            'label' => 'Manuele KR zonder berekening',
            'metric_key' => null,
            'target_value' => null,
            'target_unit' => '',
            'position' => 99,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'presentatiekwaliteit']));

        $response->assertOk();
        $response->assertSee('Manuele KR zonder berekening');
    }
}
