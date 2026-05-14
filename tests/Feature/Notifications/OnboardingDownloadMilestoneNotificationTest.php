<?php

namespace Tests\Feature\Notifications;

use App\Notifications\OnboardingDownloadMilestoneNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class OnboardingDownloadMilestoneNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_does_not_imply_obligation_to_give_back(): void
    {
        $notification = new OnboardingDownloadMilestoneNotification(5);
        $notifiable = (object) ['first_name' => 'Anna'];

        $mail = $notification->toMail($notifiable);

        $this->assertStringNotContainsString('terug te geven', $mail->subject);
    }

    public function test_subject_uses_invitational_framing(): void
    {
        $notification = new OnboardingDownloadMilestoneNotification(5);
        $notifiable = (object) ['first_name' => 'Anna'];

        $mail = $notification->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertStringContainsString('werkt voor je bewoners', $mail->subject);
    }

    public function test_body_view_receives_download_count(): void
    {
        $notification = new OnboardingDownloadMilestoneNotification(7);
        $notifiable = (object) ['first_name' => 'Anna'];

        $mail = $notification->toMail($notifiable);

        $this->assertSame(7, $mail->viewData['downloadCount']);
    }
}
