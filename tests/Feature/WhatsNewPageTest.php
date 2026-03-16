<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsNewPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_whats_new_page_loads(): void
    {
        $response = $this->get('/wat-is-er-nieuw');

        $response->assertStatus(200);
    }

    public function test_whats_new_page_contains_heading(): void
    {
        $response = $this->get('/wat-is-er-nieuw');

        $response->assertSee('Een nieuwe Hartverwarmers');
    }

    public function test_guest_sees_whats_new_banner_on_homepage(): void
    {
        $response = $this->get('/');

        $response->assertSee('Hartverwarmers is volledig vernieuwd');
        $response->assertSee('Lees meer');
    }

    public function test_existing_user_sees_whats_new_banner(): void
    {
        $user = User::factory()->create([
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))->subDay(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertSee('Hartverwarmers is volledig vernieuwd');
    }

    public function test_new_user_does_not_see_whats_new_banner(): void
    {
        $user = User::factory()->create([
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
    }

    public function test_new_user_sees_onboarding_banner_instead(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => null,
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
        $response->assertSee('Dit kan je nu allemaal');
    }
}
