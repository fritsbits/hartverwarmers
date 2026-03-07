<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_home_page_shows_diamant_section(): void
    {
        Feature::define('diamant-goals', true);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('DIAMANT-kompas');
    }

    public function test_home_page_without_diamant_goals_feature(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('DIAMANT-kompas');
    }
}
