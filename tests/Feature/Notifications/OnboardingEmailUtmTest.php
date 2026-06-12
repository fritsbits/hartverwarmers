<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use App\Notifications\OnboardingTopFiveNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingEmailUtmTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);

        $html = (new WelcomeNotification)->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=welcome', $html);
        $this->assertStringContainsString('utm_source=lifecycle', $html);
        $this->assertStringContainsString('utm_medium=email', $html);
    }

    public function test_onboarding_first_bookmark_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);
        $fiche = Fiche::factory()->published()->create();

        $html = (new OnboardingFirstBookmarkNotification($fiche))->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=onboarding-first-bookmark', $html);
        $this->assertStringContainsString('utm_source=lifecycle', $html);
        $this->assertStringContainsString('utm_medium=email', $html);
    }

    public function test_onboarding_contribute_invitation_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);

        $html = (new OnboardingDownloadMilestoneNotification(downloadCount: 5))->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=onboarding-contribute', $html);
        $this->assertStringContainsString('utm_source=lifecycle', $html);
        $this->assertStringContainsString('utm_medium=email', $html);
    }

    public function test_onboarding_milestone_10_bookmarks_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(2)->create(['initiative_id' => $initiative->id]);

        $html = (new OnboardingMilestone10BookmarksNotification(bookmarkCount: 10))->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=onboarding-10-bookmarks', $html);
        $this->assertStringContainsString('utm_content=initiative', $html);
        $this->assertStringContainsString('utm_content=create-fiche', $html);
    }

    public function test_onboarding_milestone_50_bookmarks_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);

        $html = (new OnboardingMilestone50BookmarksNotification(bookmarkCount: 50))->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=onboarding-50-bookmarks', $html);
        $this->assertStringContainsString('utm_content=contributors', $html);
        $this->assertStringContainsString('utm_content=diamantjes', $html);
    }

    public function test_onboarding_curated_activities_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);
        Fiche::factory()->published()->withDiamond()->count(3)->create();

        $html = (new OnboardingCuratedActivitiesNotification)->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=onboarding-curated', $html);
        $this->assertStringContainsString('utm_content=fiche', $html);
        $this->assertStringContainsString('utm_content=diamantjes', $html);
    }

    public function test_onboarding_top_five_carries_utm(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);

        // Create fiches with bookmarks so recentFiches and allTimeFiches are populated.
        $initiative = Initiative::factory()->published()->create();
        $recentFiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $allTimeFiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // recentFiche gets a bookmark within the last 30 days.
        $booker = User::factory()->create();
        $recentFiche->likes()->create(['user_id' => $booker->id, 'type' => 'bookmark', 'created_at' => now()]);

        // allTimeFiche gets a bookmark older than 30 days so it only appears in allTimeFiches.
        $allTimeFiche->likes()->create(['user_id' => $booker->id, 'type' => 'bookmark', 'created_at' => now()->subDays(60)]);

        $html = (new OnboardingTopFiveNotification)->toMail($user)->render();

        $this->assertStringContainsString('utm_campaign=onboarding-top-five', $html);
        $this->assertStringContainsString('utm_content=trending-fiche', $html);
        $this->assertStringContainsString('utm_content=alltime-fiche', $html);
        $this->assertStringContainsString('utm_content=create-fiche', $html);
    }
}
