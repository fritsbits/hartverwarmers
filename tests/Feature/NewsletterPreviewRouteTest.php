<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterPreviewRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_route_is_available_in_local_env(): void
    {
        $this->app['env'] = 'local';

        $user = User::factory()->create();

        $response = $this->get("/dev/newsletter-preview/{$user->id}");

        $response->assertOk();
        $response->assertSee('Hartverwarmers');
    }

    public function test_preview_route_is_unavailable_in_production_env(): void
    {
        $this->app['env'] = 'production';

        $user = User::factory()->create();

        $this->get("/dev/newsletter-preview/{$user->id}")->assertNotFound();
    }
}
