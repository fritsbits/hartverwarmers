<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrDeepLinkScrollTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_init_param_renders_scroll_alpine_block(): void
    {
        $obj = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Onboarding']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'onboarding_signup_count', 'label' => 'Aanmeldingen']);
        Initiative::create([
            'objective_id' => $obj->id, 'slug' => 'onboarding-emails', 'label' => 'Onboarding-e-mails',
            'status' => 'in_progress', 'started_at' => '2026-04-02', 'position' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin?tab=onboarding&init=onboarding-emails');

        $response->assertOk();
        $response->assertSee("getElementById('initiative-onboarding-emails')", escape: false);
    }

    public function test_no_init_param_renders_no_scroll_block(): void
    {
        $obj = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Onboarding']);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => 'onboarding_signup_count', 'label' => 'Aanmeldingen']);

        $response = $this->actingAs($this->admin())->get('/admin?tab=onboarding');

        $response->assertOk();
        $response->assertDontSee('getElementById(', escape: false);
    }
}
