<?php

namespace Tests\Feature\Notifications;

use App\Models\Comment;
use App\Models\Fiche;
use App\Notifications\BaseMailNotification;
use App\Notifications\FicheCommentNotification;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use App\Notifications\OnboardingTopFiveNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_fiche_comment_notification_has_rate_limited_middleware(): void
    {
        $fiche = Fiche::factory()->create();
        $comment = Comment::factory()->for($fiche, 'commentable')->create();
        $notification = new FicheCommentNotification($comment);

        $middleware = $notification->middleware();
        $rateLimited = collect($middleware)->first(fn ($m) => $m instanceof RateLimited);

        $this->assertNotNull($rateLimited);
        $this->assertInstanceOf(BaseMailNotification::class, $notification);
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
