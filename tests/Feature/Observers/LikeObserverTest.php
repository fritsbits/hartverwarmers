<?php

namespace Tests\Feature\Observers;

use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LikeObserverTest extends TestCase
{
    use RefreshDatabase;

    private function createFicheWithOwner(): array
    {
        $owner = User::factory()->create();
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->for($owner)->create(['published' => true]));

        return [$owner, $fiche];
    }

    private function addBookmarks(Fiche $fiche, int $count): void
    {
        Like::withoutEvents(function () use ($fiche, $count): void {
            Like::factory()->count($count)->bookmark()->create([
                'likeable_type' => Fiche::class,
                'likeable_id' => $fiche->id,
            ]);
        });
    }

    // ── Mail 4 — First bookmark ───────────────────────────────────────────────

    public function test_first_bookmark_sends_mail_4(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertSentTo($owner, OnboardingFirstBookmarkNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $owner->id, 'mail_key' => 'mail_4']);
    }

    public function test_mail_4_not_sent_for_second_bookmark(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        $this->addBookmarks($fiche, 1);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNotSentTo($owner, OnboardingFirstBookmarkNotification::class);
    }

    public function test_mail_4_not_sent_twice_when_already_logged(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        OnboardingEmailLog::create(['user_id' => $owner->id, 'mail_key' => 'mail_4', 'sent_at' => now()]);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNotSentTo($owner, OnboardingFirstBookmarkNotification::class);
    }

    // ── Mail 5 — 10 bookmarks ─────────────────────────────────────────────────

    public function test_10th_bookmark_sends_mail_5(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        $this->addBookmarks($fiche, 9);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertSentTo($owner, OnboardingMilestone10BookmarksNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $owner->id, 'mail_key' => 'mail_5']);
    }

    public function test_mail_5_not_sent_at_9_bookmarks(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        $this->addBookmarks($fiche, 8);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNotSentTo($owner, OnboardingMilestone10BookmarksNotification::class);
    }

    public function test_mail_5_not_sent_twice(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        $this->addBookmarks($fiche, 9);
        OnboardingEmailLog::create(['user_id' => $owner->id, 'mail_key' => 'mail_5', 'sent_at' => now()]);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNotSentTo($owner, OnboardingMilestone10BookmarksNotification::class);
    }

    // ── Mail 6 — 50 bookmarks ─────────────────────────────────────────────────

    public function test_50th_bookmark_sends_mail_6(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        $this->addBookmarks($fiche, 49);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertSentTo($owner, OnboardingMilestone50BookmarksNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $owner->id, 'mail_key' => 'mail_6']);
    }

    public function test_mail_6_not_sent_twice(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();
        $this->addBookmarks($fiche, 49);
        OnboardingEmailLog::create(['user_id' => $owner->id, 'mail_key' => 'mail_6', 'sent_at' => now()]);

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNotSentTo($owner, OnboardingMilestone50BookmarksNotification::class);
    }

    // ── Non-bookmark likes ────────────────────────────────────────────────────

    public function test_kudos_like_does_not_trigger_any_notification(): void
    {
        Notification::fake();
        [$owner, $fiche] = $this->createFicheWithOwner();

        Like::factory()->kudos()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNothingSent();
    }

    public function test_opt_out_prevents_milestone_notification(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['notify_on_kudos_milestones' => false]);
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->for($owner)->create(['published' => true]));

        Like::factory()->bookmark()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        Notification::assertNothingSent();
    }
}
