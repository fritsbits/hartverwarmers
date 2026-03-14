<?php

namespace Tests\Feature;

use App\Mail\SupportMessage;
use App\Models\Fiche;
use App\Models\User;
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

    public function test_support_message_mailable_has_correct_envelope(): void
    {
        $mailable = new SupportMessage(
            senderName: 'Jan Janssen',
            senderEmail: 'jan@example.com',
            senderMessage: 'Ik wil graag bijdragen.',
        );

        $mailable->assertHasSubject('Steunbericht via Hartverwarmers — Jan Janssen');
        $mailable->assertTo(config('mail.support_address'));
        $mailable->assertHasReplyTo('jan@example.com');
    }

    public function test_about_page_shows_dynamic_stats(): void
    {
        $user = User::factory()->create(['organisation' => 'WZC Test']);
        Fiche::factory()->for($user)->published()->create();

        $response = $this->get('/over-ons');

        $response->assertOk();
        $response->assertViewHas('aboutStats');
        $data = $response->viewData('aboutStats');
        $this->assertArrayHasKey('fiches_count', $data);
        $this->assertArrayHasKey('contributors_count', $data);
        $this->assertArrayHasKey('users_count', $data);
        $this->assertGreaterThan(0, $data['fiches_count']);
    }
}
