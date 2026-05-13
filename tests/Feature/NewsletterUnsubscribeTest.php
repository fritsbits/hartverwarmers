<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NewsletterUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signed_url_unsubscribes_the_user(): void
    {
        $user = User::factory()->create(['newsletter_unsubscribed_at' => null]);

        $url = URL::signedRoute('newsletter.unsubscribe', ['user' => $user->id]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertSeeText('uitgeschreven');
        $this->assertNotNull($user->fresh()->newsletter_unsubscribed_at);
    }

    public function test_tampered_signature_returns_403(): void
    {
        $user = User::factory()->create();

        $url = URL::signedRoute('newsletter.unsubscribe', ['user' => $user->id]).'-tampered';

        $this->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->newsletter_unsubscribed_at);
    }

    public function test_already_unsubscribed_user_sees_confirmation_idempotent(): void
    {
        $user = User::factory()->create([
            'newsletter_unsubscribed_at' => now()->subDays(5),
        ]);

        $url = URL::signedRoute('newsletter.unsubscribe', ['user' => $user->id]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertSeeText('uitgeschreven');
    }
}
