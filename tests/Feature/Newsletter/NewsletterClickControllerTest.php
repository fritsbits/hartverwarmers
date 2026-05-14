<?php

namespace Tests\Feature\Newsletter;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NewsletterClickControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_click_redirects_to_destination_and_updates_last_visited_at(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['last_visited_at' => null]);
        $destination = url('/initiatieven');

        $url = URL::signedRoute('newsletter.click', [
            'user' => $user->id,
            'to' => base64_encode($destination),
        ]);

        $this->get($url)->assertRedirect($destination);

        $this->assertNotNull($user->fresh()->last_visited_at);
        $this->assertTrue($user->fresh()->last_visited_at->equalTo(now()));
    }

    public function test_tampered_signature_returns_403(): void
    {
        $user = User::factory()->create();

        $tampered = route('newsletter.click', [
            'user' => $user->id,
            'to' => base64_encode(url('/initiatieven')),
        ]);

        $this->get($tampered)->assertStatus(403);
    }

    public function test_destination_outside_app_url_falls_back_to_home(): void
    {
        $user = User::factory()->create();

        $url = URL::signedRoute('newsletter.click', [
            'user' => $user->id,
            'to' => base64_encode('https://evil.example.com/phish'),
        ]);

        $this->get($url)->assertRedirect(route('home'));
    }

    public function test_missing_to_param_redirects_home(): void
    {
        $user = User::factory()->create();

        $url = URL::signedRoute('newsletter.click', ['user' => $user->id]);

        $this->get($url)->assertRedirect(route('home'));
    }
}
