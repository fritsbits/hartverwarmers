<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserNewsletterUnsubscribedAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_unsubscribed_at_is_nullable_and_castable(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->newsletter_unsubscribed_at);

        $user->update(['newsletter_unsubscribed_at' => now()]);

        $this->assertInstanceOf(Carbon::class, $user->fresh()->newsletter_unsubscribed_at);
    }

    public function test_postal_address_config_is_set(): void
    {
        $this->assertSame(
            'Impact Studio, Kasteeldreef 47, 1083 Ganshoren',
            config('mail.from.postal_address')
        );
    }
}
