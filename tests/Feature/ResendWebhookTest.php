<?php

namespace Tests\Feature;

use App\Models\EmailBounce;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ResendWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $rawKey = 'super-secret-signing-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.resend.webhook_secret' => 'whsec_'.base64_encode($this->rawKey)]);
    }

    public function test_permanent_bounce_is_recorded(): void
    {
        $this->postWebhook([
            'type' => 'email.bounced',
            'data' => [
                'to' => ['dood@zorgcentrum.be'],
                'bounce' => ['type' => 'Permanent', 'message' => 'Mailbox does not exist'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('email_bounces', [
            'email' => 'dood@zorgcentrum.be',
            'type' => 'bounce',
            'reason' => 'Mailbox does not exist',
        ]);
    }

    public function test_temporary_bounce_is_ignored(): void
    {
        $this->postWebhook([
            'type' => 'email.bounced',
            'data' => [
                'to' => ['vol@zorgcentrum.be'],
                'bounce' => ['type' => 'Transient', 'message' => 'Mailbox full'],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('email_bounces', 0);
    }

    public function test_complaint_is_recorded(): void
    {
        $this->postWebhook([
            'type' => 'email.complained',
            'data' => ['to' => ['boos@zorgcentrum.be']],
        ])->assertOk();

        $this->assertDatabaseHas('email_bounces', [
            'email' => 'boos@zorgcentrum.be',
            'type' => 'complaint',
        ]);
    }

    public function test_repeated_bounce_does_not_duplicate(): void
    {
        $payload = [
            'type' => 'email.bounced',
            'data' => [
                'to' => ['dood@zorgcentrum.be'],
                'bounce' => ['type' => 'Permanent', 'message' => 'Mailbox does not exist'],
            ],
        ];

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseCount('email_bounces', 1);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $body = json_encode(['type' => 'email.bounced', 'data' => ['to' => ['x@y.be']]]);

        $this->call('POST', '/webhooks/resend', [], [], [], [
            'HTTP_svix-id' => 'msg_1',
            'HTTP_svix-timestamp' => '1700000000',
            'HTTP_svix-signature' => 'v1,not-a-real-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(401);

        $this->assertDatabaseCount('email_bounces', 0);
    }

    public function test_missing_secret_rejects_request(): void
    {
        config(['services.resend.webhook_secret' => null]);

        $this->postWebhook([
            'type' => 'email.bounced',
            'data' => ['to' => ['x@y.be'], 'bounce' => ['type' => 'Permanent']],
        ])->assertStatus(401);
    }

    public function test_bounced_user_is_excluded_from_reactivation_cohort(): void
    {
        $deliverable = User::factory()->create([
            'email_verified_at' => now(),
            'last_visited_at' => null,
            'created_at' => now()->subDays(120),
        ]);

        $bounced = User::factory()->create([
            'email_verified_at' => now(),
            'last_visited_at' => null,
            'created_at' => now()->subDays(120),
        ]);
        EmailBounce::factory()->create(['email' => $bounced->email]);

        $cohort = User::reactivationCohort()->pluck('id');

        $this->assertTrue($cohort->contains($deliverable->id));
        $this->assertFalse($cohort->contains($bounced->id));
    }

    public function test_mail_to_bounced_address_is_suppressed_but_not_to_deliverable(): void
    {
        $sent = [];
        Event::listen(NotificationSent::class, function (NotificationSent $event) use (&$sent): void {
            $sent[] = $event->notifiable;
        });

        $deliverable = User::factory()->create();
        $bounced = User::factory()->create();
        EmailBounce::factory()->create(['email' => $bounced->email]);

        $deliverable->notify(new VerifyEmail);
        $bounced->notify(new VerifyEmail);

        $this->assertCount(1, $sent);
        $this->assertTrue($sent[0]->is($deliverable));
    }

    public function test_long_bounce_message_is_truncated_and_recorded(): void
    {
        $longMessage = str_repeat('SMTP 550 5.1.1 the email account does not exist; ', 100); // ~4800 chars

        $this->postWebhook([
            'type' => 'email.bounced',
            'data' => [
                'to' => ['dood@zorgcentrum.be'],
                'bounce' => ['type' => 'Permanent', 'message' => $longMessage],
            ],
        ])->assertOk();

        $bounce = EmailBounce::firstWhere('email', 'dood@zorgcentrum.be');

        $this->assertNotNull($bounce);
        $this->assertLessThanOrEqual(2000, mb_strlen((string) $bounce->reason));
        $this->assertStringStartsWith('SMTP 550', (string) $bounce->reason);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload): TestResponse
    {
        $body = json_encode($payload);
        $id = 'msg_test';
        $timestamp = '1700000000';
        $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $this->rawKey, true));

        return $this->call('POST', '/webhooks/resend', [], [], [], [
            'HTTP_svix-id' => $id,
            'HTTP_svix-timestamp' => $timestamp,
            'HTTP_svix-signature' => "v1,{$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }
}
