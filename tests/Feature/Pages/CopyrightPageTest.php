<?php

namespace Tests\Feature\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyrightPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_copyright_page_is_accessible(): void
    {
        $this->get('/auteursrecht')
            ->assertStatus(200)
            ->assertSee('Auteursrecht');
    }

    public function test_copyright_page_has_required_dsa_content(): void
    {
        $this->get('/auteursrecht')
            ->assertSee('info@hartverwarmers.be')
            ->assertSee('notice-and-takedown');
    }
}
