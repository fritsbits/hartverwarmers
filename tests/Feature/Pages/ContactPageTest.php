<?php

namespace Tests\Feature\Pages;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_returns_ok(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSeeText('Praat met Frederik');
        $response->assertSeeText('Waarover gaat het?');
    }

    public function test_contact_page_introduces_frederik_as_the_recipient(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSeeText('Frederik Vincx');
        $response->assertSee('/img/about/frederik-vincx.webp');
        $response->assertSeeText('binnen een paar dagen');
    }

    public function test_contact_page_preselects_reason_from_query(): void
    {
        $response = $this->get('/contact?reden=feedback');

        $response->assertOk();
        $response->assertSee('Idee of feedback');
    }

    public function test_contact_route_is_named(): void
    {
        $this->assertSame(url('/contact'), route('contact'));
        $this->assertSame(url('/contact').'?reden=feedback', route('contact', ['reden' => 'feedback']));
    }

    public function test_homepage_nav_and_footer_link_to_contact(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('contact'));
        $response->assertSeeText('Contact');
    }

    public function test_feedback_button_shows_on_public_pages(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('Help je ons beter worden?');
        $response->assertSee(route('contact', ['reden' => 'feedback']));
    }

    public function test_feedback_button_is_hidden_on_contact_page(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertDontSeeText('Help je ons beter worden?');
    }

    public function test_feedback_button_is_hidden_in_admin_area(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.health'));

        $response->assertOk();
        $response->assertDontSeeText('Help je ons beter worden?');
    }

    public function test_contact_page_preselects_samenwerking_reason(): void
    {
        $response = $this->get('/contact?reden=samenwerking');

        $response->assertOk();
        $response->assertSee('Samenwerking of steun');
    }
}
