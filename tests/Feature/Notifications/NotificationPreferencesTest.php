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

    public function test_update_notifications_saves_all_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('profile.notifications.update'), [
            'notification_frequency' => 'weekly',
            'notify_on_kudos_milestones' => '1',
            'notify_on_onboarding_emails' => '0',
        ])->assertRedirect(route('profile.notifications'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'notification_frequency' => 'weekly',
            'notify_on_kudos_milestones' => true,
            'notify_on_onboarding_emails' => false,
        ]);
    }

    public function test_update_notifications_rejects_invalid_frequency(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('profile.notifications.update'), [
            'notification_frequency' => 'instantly',
        ])->assertSessionHasErrors('notification_frequency');
    }

    public function test_notifications_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('profile.notifications'))->assertOk();
    }

    public function test_notifications_page_preselects_saved_frequency(): void
    {
        $user = User::factory()->create(['notification_frequency' => 'weekly']);
        $this->actingAs($user);

        $response = $this->get(route('profile.notifications'));

        $response->assertOk();
        $this->assertEquals('weekly', $this->checkedRadioValue($response->getContent()));
    }

    public function test_notifications_page_falls_back_to_daily_when_value_empty(): void
    {
        $user = User::factory()->create();
        \DB::table('users')->where('id', $user->id)->update(['notification_frequency' => '']);
        $this->actingAs($user);

        $response = $this->get(route('profile.notifications'));

        $this->assertEquals('daily', $this->checkedRadioValue($response->getContent()));
    }

    private function checkedRadioValue(string $html): ?string
    {
        if (preg_match('/<ui-radio[^>]*\bchecked\b[^>]*>/i', $html, $m)
            && preg_match('/value="([^"]+)"/', $m[0], $v)) {
            return $v[1];
        }

        return null;
    }
}
