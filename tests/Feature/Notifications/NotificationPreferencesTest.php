<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsubscribe_sets_frequency_to_never(): void
    {
        $user = User::factory()->create(['notification_frequency' => 'daily']);
        $url = URL::signedRoute('notifications.unsubscribe', ['user' => $user->id]);

        $this->get($url)->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'notification_frequency' => 'never',
        ]);
    }

    public function test_unsubscribe_requires_valid_signature(): void
    {
        $user = User::factory()->create();

        $this->get('/meldingen/uitschrijven?user='.$user->id)->assertStatus(403);
    }
}
