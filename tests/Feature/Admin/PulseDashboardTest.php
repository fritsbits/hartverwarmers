<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PulseDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_forbidden(): void
    {
        $response = $this->get('/pulse');

        $response->assertForbidden();
    }

    public function test_contributor_is_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pulse');

        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/pulse');

        $response->assertOk();
    }
}
