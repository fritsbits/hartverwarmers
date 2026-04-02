<?php

namespace Tests\Feature\Notifications;

use App\Notifications\OnboardingDownloadMilestoneNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class OnboardingDownloadMilestoneNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_message_contains_download_count(): void
    {
        $notification = new OnboardingDownloadMilestoneNotification(7);
        $notifiable = (object) ['first_name' => 'Anna'];

        $mail = $notification->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertStringContainsString('7', $mail->subject);
    }

    public function test_mail_subject_contains_download_count(): void
    {
        $notification = new OnboardingDownloadMilestoneNotification(5);

        $notifiable = (object) ['first_name' => 'Lien'];
        $mail = $notification->toMail($notifiable);

        $this->assertStringContainsString('5', $mail->subject);
    }
}
