<?php

namespace Tests\Feature\Users;

use App\Listeners\SendWelcomeNotification;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WelcomeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_notification_is_sent_after_verification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $listener = new SendWelcomeNotification;
        $listener->handle(new Verified($user));

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_welcome_notification_contains_correct_content(): void
    {
        $user = User::factory()->create(['first_name' => 'Els']);

        $notification = new WelcomeNotification;
        $mail = $notification->toMail($user);

        $this->assertStringContains('Welkom bij Hartverwarmers, Els!', $mail->subject);

        $rendered = $mail->render()->toHtml();
        $this->assertStringContainsString('Hoi Els!', $rendered);
        $this->assertStringContainsString('/initiatieven', $rendered);
        $this->assertStringContainsString('warme water', $rendered);
        $this->assertStringContainsString('fiches', $rendered);
    }

    public function test_welcome_notification_has_initiatieven_link(): void
    {
        $user = User::factory()->create();

        $notification = new WelcomeNotification;
        $mail = $notification->toMail($user);

        $rendered = $mail->render()->toHtml();
        $this->assertStringContainsString(url('/initiatieven'), $rendered);
    }

    public function test_verified_event_listener_is_registered(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        event(new Verified($user));

        Event::assertListening(Verified::class, SendWelcomeNotification::class);
    }

    public function test_admin_can_preview_welcome_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.preview', 'welcome'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
