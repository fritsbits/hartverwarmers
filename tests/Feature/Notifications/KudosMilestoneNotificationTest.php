<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KudosMilestoneNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_first_bookmark_notification_when_kudos_milestones_enabled(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['notify_on_kudos_milestones' => true]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);
        $bookmarker = User::factory()->create();

        Like::factory()->create([
            'user_id' => $bookmarker->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        Notification::assertSentTo($owner, OnboardingFirstBookmarkNotification::class);
    }

    public function test_does_not_send_milestone_notification_when_kudos_milestones_disabled(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['notify_on_kudos_milestones' => false]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);
        $bookmarker = User::factory()->create();

        Like::factory()->create([
            'user_id' => $bookmarker->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        Notification::assertNotSentTo($owner, OnboardingFirstBookmarkNotification::class);
    }

    public function test_sends_10_bookmark_milestone_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['notify_on_kudos_milestones' => true]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);

        // Insert 9 existing bookmarks directly, bypassing the observer
        $now = now()->toDateTimeString();
        $rows = [];
        for ($i = 0; $i < 9; $i++) {
            $rows[] = [
                'user_id' => User::factory()->create()->id,
                'likeable_type' => Fiche::class,
                'likeable_id' => $fiche->id,
                'type' => 'bookmark',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('likes')->insert($rows);

        // The 10th bookmark triggers the milestone
        Like::factory()->create([
            'user_id' => User::factory()->create()->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        Notification::assertSentTo($owner, OnboardingMilestone10BookmarksNotification::class);
    }

    public function test_sends_50_bookmark_milestone_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['notify_on_kudos_milestones' => true]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);

        // Insert 49 existing bookmarks directly, bypassing the observer
        $now = now()->toDateTimeString();
        $rows = [];
        for ($i = 0; $i < 49; $i++) {
            $rows[] = [
                'user_id' => User::factory()->create()->id,
                'likeable_type' => Fiche::class,
                'likeable_id' => $fiche->id,
                'type' => 'bookmark',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('likes')->insert($rows);

        // The 50th bookmark triggers the milestone
        Like::factory()->create([
            'user_id' => User::factory()->create()->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        Notification::assertSentTo($owner, OnboardingMilestone50BookmarksNotification::class);
    }
}
