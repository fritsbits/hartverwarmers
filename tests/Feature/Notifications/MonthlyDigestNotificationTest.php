<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\MonthlyDigestNotification;
use App\Services\MonthlyDigest\Payload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MonthlyDigestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_is_static_inspiration_line(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $mail = (new MonthlyDigestNotification($payload))->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Verse ideeën voor de komende weken', $mail->subject);
    }

    public function test_uses_monthly_digest_view(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $mail = (new MonthlyDigestNotification($payload))->toMail($user);

        $this->assertSame('emails.monthly-digest', $mail->view);
    }

    public function test_view_data_includes_payload_and_notifiable(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $mail = (new MonthlyDigestNotification($payload))->toMail($user);

        $this->assertSame($payload, $mail->viewData['payload']);
        $this->assertSame($user->id, $mail->viewData['notifiable']->id);
    }

    private function emptyPayload(): Payload
    {
        return new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 0,
            sentAt: now(),
        );
    }
}
