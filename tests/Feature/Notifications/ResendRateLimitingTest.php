<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
use App\Models\User;
use App\Notifications\BaseMailNotification;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use App\Notifications\OnboardingTopFiveNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Middleware\RateLimited;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResendRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_mail_notification_middleware_contains_rate_limited(): void
    {
        $notification = new OnboardingCuratedActivitiesNotification;
        $middleware = $notification->middleware();

        $this->assertNotEmpty($middleware);
        $this->assertInstanceOf(RateLimited::class, $middleware[0]);
    }

    #[DataProvider('queuedMailNotificationProvider')]
    public function test_all_queued_mail_notifications_extend_base_mail_notification(object $notification): void
    {
        $this->assertInstanceOf(BaseMailNotification::class, $notification);
    }

    #[DataProvider('queuedMailNotificationProvider')]
    public function test_all_queued_mail_notifications_have_rate_limited_middleware(object $notification): void
    {
        $middleware = $notification->middleware();

        $rateLimited = collect($middleware)->first(fn ($m) => $m instanceof RateLimited);

        $this->assertNotNull($rateLimited, get_class($notification).' is missing RateLimited middleware');
    }

    public static function queuedMailNotificationProvider(): array
    {
        return [
            'WelcomeNotification' => [new WelcomeNotification],
            'OnboardingCuratedActivitiesNotification' => [new OnboardingCuratedActivitiesNotification],
            'OnboardingTopFiveNotification' => [new OnboardingTopFiveNotification],
            'OnboardingDownloadMilestoneNotification' => [new OnboardingDownloadMilestoneNotification(7)],
            'OnboardingMilestone10BookmarksNotification' => [new OnboardingMilestone10BookmarksNotification(10)],
            'OnboardingMilestone50BookmarksNotification' => [new OnboardingMilestone50BookmarksNotification(50)],
        ];
    }

    #[DataProvider('queuedMailNotificationProvider')]
    public function test_rate_limited_notifications_use_time_based_retries(object $notification): void
    {
        $job = new SendQueuedNotifications(User::factory()->create(), $notification);

        $retryUntil = $job->retryUntil();

        $this->assertNotNull(
            $retryUntil,
            get_class($notification).' must define retryUntil() — the RateLimited middleware releases '
            .'jobs back onto the queue (each release is an attempt), so a count-based ceiling throws '
            .'MaxAttemptsExceededException on burst sends before the job can send.'
        );
        $this->assertGreaterThan(now()->getTimestamp(), $retryUntil->getTimestamp());
    }

    #[DataProvider('queuedMailNotificationProvider')]
    public function test_rate_limited_notifications_cap_genuine_exceptions(object $notification): void
    {
        $job = new SendQueuedNotifications(User::factory()->create(), $notification);

        $this->assertNotNull(
            $job->maxExceptions,
            get_class($notification).' must define maxExceptions so a genuinely broken send fails fast '
            .'instead of retrying for the whole retryUntil window.'
        );
    }

    public function test_onboarding_first_bookmark_notification_has_rate_limited_middleware(): void
    {
        $fiche = Fiche::factory()->create();
        $notification = new OnboardingFirstBookmarkNotification($fiche);

        $middleware = $notification->middleware();
        $rateLimited = collect($middleware)->first(fn ($m) => $m instanceof RateLimited);

        $this->assertNotNull($rateLimited);
        $this->assertInstanceOf(BaseMailNotification::class, $notification);
    }
}
