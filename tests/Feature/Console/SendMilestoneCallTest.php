<?php

namespace Tests\Feature\Console;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\MilestoneCallNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendMilestoneCallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'newsletter.milestone_call_active' => true,
            'newsletter.milestone_call_target' => 10,
        ]);
    }

    private function contributor(int $fiches = 1, array $attributes = []): User
    {
        $user = User::factory()->create(['email_verified_at' => now()->subYear(), ...$attributes]);
        Fiche::factory()->count($fiches)->published()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_sends_once_to_each_verified_subscribed_contributor(): void
    {
        $contributor = $this->contributor();
        $this->contributor(attributes: ['newsletter_unsubscribed_at' => now()]);
        $this->contributor(attributes: ['email_verified_at' => null]);
        $draftOnly = User::factory()->create(['email_verified_at' => now()]);
        Fiche::factory()->create(['user_id' => $draftOnly->id]);
        Notification::fake();

        $this->artisan('newsletter:send-milestone-call')->assertExitCode(0);
        $this->artisan('newsletter:send-milestone-call');

        Notification::assertSentTimes(MilestoneCallNotification::class, 1);
        Notification::assertSentTo($contributor, MilestoneCallNotification::class);
        $this->assertSame(1, OnboardingEmailLog::where('mail_key', config('newsletter.milestone_call_mail_key'))->count());
    }

    public function test_stops_once_the_target_is_reached(): void
    {
        $this->contributor(fiches: 10);
        Notification::fake();

        $this->artisan('newsletter:send-milestone-call')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_does_nothing_when_flag_is_off(): void
    {
        config(['newsletter.milestone_call_active' => false]);
        $this->contributor();
        Notification::fake();

        $this->artisan('newsletter:send-milestone-call');

        Notification::assertNothingSent();
    }

    public function test_user_held_back_by_the_daily_cap_gets_it_the_next_day(): void
    {
        $user = $this->contributor();
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()->subHours(2)]);
        Notification::fake();

        $this->artisan('newsletter:send-milestone-call');
        Notification::assertNothingSent();

        $this->travel(1)->day();
        $this->artisan('newsletter:send-milestone-call');
        Notification::assertSentTo($user, MilestoneCallNotification::class);
    }

    public function test_mail_lists_the_initiatives_with_the_fewest_fiches(): void
    {
        $user = $this->contributor(fiches: 2);
        $thin = Initiative::factory()->create(['title' => 'Tuinieren', 'published' => true]);
        Fiche::query()->update(['initiative_id' => $thin->id]);
        $rich = Initiative::factory()->create(['title' => 'Muziek', 'published' => true]);
        Fiche::factory()->count(5)->published()->create(['initiative_id' => $rich->id]);
        Notification::fake();

        $this->artisan('newsletter:send-milestone-call');

        Notification::assertSentTo($user, MilestoneCallNotification::class, function (MilestoneCallNotification $notification) use ($user) {
            $mail = $notification->toMail($user);
            $html = view($mail->view, $mail->viewData)->render();

            return $mail->subject === 'Nog 3 activiteiten tot 10'
                && $notification->ownFichesCount === 2
                && $notification->content->needsLove->first()->title === 'Tuinieren'
                && str_contains($html, '2 daarvan zijn van jou')
                && str_contains($html, 'Muziek (5)');
        });
    }

    public function test_to_option_sends_one_test_mail_without_logging(): void
    {
        config(['newsletter.milestone_call_active' => false]);
        $me = User::factory()->create(['email' => 'me@example.com']);
        Notification::fake();

        $this->artisan('newsletter:send-milestone-call --to=me@example.com')->assertExitCode(0);

        Notification::assertSentTo($me, MilestoneCallNotification::class);
        $this->assertSame(0, OnboardingEmailLog::count());
    }
}
