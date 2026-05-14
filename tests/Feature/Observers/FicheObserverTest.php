<?php

namespace Tests\Feature\Observers;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\FicheDiamondAwardedNotification;
use App\Observers\FicheObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FicheObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_job_on_fiche_creation(): void
    {
        Queue::fake();

        $fiche = Fiche::factory()->create();

        Queue::assertPushed(AssignFicheIcon::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function test_dispatches_job_on_title_update(): void
    {
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->create());

        Queue::fake();

        $fiche->update(['title' => 'Nieuwe titel']);

        Queue::assertPushed(AssignFicheIcon::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function test_does_not_dispatch_job_when_title_unchanged(): void
    {
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->create());

        Queue::fake();

        $fiche->update(['description' => 'Updated description only']);

        Queue::assertNotPushed(AssignFicheIcon::class);
    }

    public function test_fires_diamantje_email_when_has_diamond_flips_to_true(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => false]);

        $fiche->update(['has_diamond' => true]);

        Notification::assertSentTo($user, FicheDiamondAwardedNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', [
            'user_id' => $user->id,
            'mail_key' => "diamantje-{$fiche->id}",
        ]);
    }

    public function test_does_not_fire_when_only_other_fields_change(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => false, 'title' => 'Oud']);

        $fiche->update(['title' => 'Nieuw']);

        Notification::assertNotSentTo($user, FicheDiamondAwardedNotification::class);
    }

    public function test_does_not_fire_when_has_diamond_flips_to_false(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => true]);

        $fiche->update(['has_diamond' => false]);

        Notification::assertNotSentTo($user, FicheDiamondAwardedNotification::class);
    }

    public function test_does_not_fire_twice_for_the_same_fiche(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => false]);

        $fiche->update(['has_diamond' => true]);
        $fiche->update(['has_diamond' => false]);
        $fiche->update(['has_diamond' => true]);

        Notification::assertSentToTimes($user, FicheDiamondAwardedNotification::class, 1);
    }

    public function test_respects_notify_on_kudos_milestones_preference(): void
    {
        Notification::fake();
        $user = User::factory()->create(['notify_on_kudos_milestones' => false]);
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => false]);

        $fiche->update(['has_diamond' => true]);

        Notification::assertNotSentTo($user, FicheDiamondAwardedNotification::class);
        $this->assertDatabaseMissing('onboarding_email_log', [
            'user_id' => $user->id,
            'mail_key' => "diamantje-{$fiche->id}",
        ]);
    }

    public function test_skips_when_fiche_has_no_owner(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => false]);

        // Simulate a null owner by manipulating model state directly, then
        // invoking the observer's updated hook (DB NOT NULL prevents null user_id).
        $fiche->syncOriginal();
        $fiche->has_diamond = true;
        $fiche->syncChanges();
        $fiche->setRelation('user', null);

        app(FicheObserver::class)->updated($fiche);

        $this->assertDatabaseMissing('onboarding_email_log', [
            'mail_key' => "diamantje-{$fiche->id}",
        ]);
    }

    public function test_diamantje_email_is_exempt_from_24h_cap(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHour(),
        ]);
        $fiche = Fiche::factory()->for($user)->create(['has_diamond' => false]);

        $fiche->update(['has_diamond' => true]);

        // Sends despite recent mail_1 (cap-exempt as recipient).
        Notification::assertSentTo($user, FicheDiamondAwardedNotification::class);
    }
}
