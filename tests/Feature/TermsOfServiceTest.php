<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsOfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_contains_warranty_clause(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('Garandeert')
            ->assertSee('geen inbreuk');
    }

    public function test_terms_page_contains_indemnification_clause(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('vrijwaart');
    }

    public function test_terms_page_contains_removal_rights(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('auteursrechtinbreuk')
            ->assertSee('verwijderen');
    }

    public function test_terms_page_contains_repeat_offender_policy(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('herhaaldelijke');
    }

    public function test_terms_page_links_to_copyright_page(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee(route('legal.copyright'));
    }
}
