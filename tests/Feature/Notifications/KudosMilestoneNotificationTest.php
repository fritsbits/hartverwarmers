<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use App\Notifications\OnboardingFirstBookmarkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        Notification::assertNothingSent();
    }
}
