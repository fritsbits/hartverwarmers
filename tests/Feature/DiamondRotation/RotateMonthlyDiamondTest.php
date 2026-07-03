<?php

namespace Tests\Feature\DiamondRotation;

use App\Models\DiamondRotation;
use App\Models\Fiche;
use App\Models\User;
use App\Notifications\FicheDiamondAwardedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RotateMonthlyDiamondTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.admin_address' => 'admin@example.com']);
        Carbon::setTestNow('2026-08-01 06:00:00');
    }

    public function test_awards_the_planned_pick(): void
    {
        $pick = Fiche::factory()->published()->create();
        $rotation = DiamondRotation::factory()->create([
            'month' => '2026-08-01',
            'fiche_id' => $pick->id,
            'suggested_fiche_ids' => [$pick->id],
        ]);

        $this->artisan('diamonds:rotate')->assertSuccessful();

        $pick->refresh();
        $this->assertTrue($pick->has_diamond);
        $this->assertNotNull($pick->diamond_awarded_at);
        $this->assertNotNull($rotation->fresh()->awarded_at);
    }

    public function test_notifies_the_fiche_author_on_award(): void
    {
        Notification::fake();

        $author = User::factory()->create(['notify_on_kudos_milestones' => true]);
        $pick = Fiche::factory()->published()->for($author)->create();
        DiamondRotation::factory()->create(['month' => '2026-08-01', 'fiche_id' => $pick->id]);

        $this->artisan('diamonds:rotate')->assertSuccessful();

        Notification::assertSentTo($author, FicheDiamondAwardedNotification::class);
    }

    public function test_does_not_award_twice_in_the_same_month(): void
    {
        $awarded = Fiche::factory()->published()->withDiamond()->create();
        DiamondRotation::factory()->awarded()->create([
            'month' => '2026-08-01',
            'fiche_id' => $awarded->id,
        ]);
        $other = Fiche::factory()->published()->create();

        $this->artisan('diamonds:rotate')
            ->expectsOutputToContain('already awarded')
            ->assertSuccessful();

        $this->assertFalse($other->fresh()->has_diamond);
    }

    public function test_falls_back_to_next_suggestion_when_pick_became_ineligible(): void
    {
        $pick = Fiche::factory()->create(['published' => false]);
        $backup = Fiche::factory()->published()->create();

        $rotation = DiamondRotation::factory()->create([
            'month' => '2026-08-01',
            'fiche_id' => $pick->id,
            'suggested_fiche_ids' => [$pick->id, $backup->id],
        ]);

        $this->artisan('diamonds:rotate')->assertSuccessful();

        $this->assertFalse($pick->fresh()->has_diamond);
        $this->assertTrue($backup->fresh()->has_diamond);
        $this->assertSame($backup->id, $rotation->fresh()->fiche_id);
    }

    public function test_creates_rotation_on_the_fly_when_suggestion_mail_never_ran(): void
    {
        $best = Fiche::factory()->published()->withScores(quality: 90)->create();
        Fiche::factory()->published()->withScores(quality: 70)->create();

        $this->artisan('diamonds:rotate')->assertSuccessful();

        $this->assertTrue($best->fresh()->has_diamond);

        $rotation = DiamondRotation::forMonth(Carbon::parse('2026-08-01'))->first();
        $this->assertNotNull($rotation);
        $this->assertSame($best->id, $rotation->fiche_id);
        $this->assertNotNull($rotation->awarded_at);
    }

    public function test_alerts_admin_and_fails_when_nothing_can_be_awarded(): void
    {
        Mail::fake();

        Fiche::factory()->published()->withDiamond()->create();

        $this->artisan('diamonds:rotate')
            ->expectsOutputToContain('No eligible fiche')
            ->assertFailed();
    }
}
