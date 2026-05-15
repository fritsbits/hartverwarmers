<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrOnboardingTabTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_renders_onboarding_emails_initiative_section(): void
    {
        $obj = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Onboarding']);
        KeyResult::factory()->create([
            'objective_id' => $obj->id,
            'metric_key' => 'onboarding_signup_count',
            'label' => 'Aanmeldingen',
        ]);
        Initiative::create([
            'objective_id' => $obj->id,
            'slug' => 'onboarding-emails',
            'label' => 'Onboarding-e-mails',
            'status' => 'in_progress',
            'started_at' => '2026-04-02',
            'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=onboarding');

        $response->assertOk();
        $response->assertSee('id="initiative-onboarding-emails"', escape: false);
        $response->assertSee('Onboarding-e-mails');
        $response->assertSee('Impact op dit doel');
        $response->assertSee('Aanmeldingen'); // KR still at top
    }

    public function test_tab_without_initiatives_shows_empty_state(): void
    {
        $obj = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Onboarding']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'onboarding_signup_count', 'label' => 'Aanmeldingen']);

        $response = $this->actingAs($this->admin())->get('/admin?tab=onboarding');

        $response->assertOk();
        $response->assertSee('Nog geen initiatief');
    }
}
