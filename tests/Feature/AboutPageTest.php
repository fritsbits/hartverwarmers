<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_returns_ok(): void
    {
        $response = $this->get('/over-ons');

        $response->assertOk();
        $response->assertSeeText('Over Hartverwarmers');
    }
}
