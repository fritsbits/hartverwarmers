<?php

namespace Tests\Feature;

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
}
