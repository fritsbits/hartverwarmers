<?php

namespace Tests\Feature\DiamondRotation;

use App\Mail\DiamondRotationSuggestionMail;
use App\Models\DiamondRotation;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDiamondRotationSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.admin_address' => 'admin@example.com']);
        Carbon::setTestNow('2026-07-27 09:00:00');
    }

    public function test_sends_suggestion_mail_and_stores_rotation_for_next_month(): void
    {
        Mail::fake();

        $best = Fiche::factory()->published()->withScores(quality: 90)->create();
        $second = Fiche::factory()->published()->withScores(quality: 80)->create();
        Fiche::factory()->published()->withScores(quality: 95)->withDiamond()->create();
        Fiche::factory()->withScores(quality: 99)->create(); // unpublished

        $this->artisan('diamonds:send-rotation-suggestion')->assertSuccessful();

        $rotation = DiamondRotation::forMonth(Carbon::parse('2026-08-01'))->first();

        $this->assertNotNull($rotation);
        $this->assertSame($best->id, $rotation->fiche_id);
        $this->assertSame('auto', $rotation->chosen_via);
        $this->assertSame([$best->id, $second->id], $rotation->suggested_fiche_ids);
        $this->assertNotNull($rotation->suggestion_sent_at);

        Mail::assertSent(DiamondRotationSuggestionMail::class, function (DiamondRotationSuggestionMail $mail) use ($best) {
            return $mail->hasTo('admin@example.com')
                && $mail->candidates->first()->is($best);
        });
    }

    public function test_does_not_resend_without_force(): void
    {
        Mail::fake();

        Fiche::factory()->published()->withScores()->create();
        DiamondRotation::factory()->create([
            'month' => '2026-08-01',
            'suggestion_sent_at' => now()->subHour(),
        ]);

        $this->artisan('diamonds:send-rotation-suggestion')->assertSuccessful();

        Mail::assertNotSent(DiamondRotationSuggestionMail::class);
    }

    public function test_force_resends_but_keeps_admin_pick(): void
    {
        Mail::fake();

        $adminPick = Fiche::factory()->published()->withScores(quality: 60)->create();
        Fiche::factory()->published()->withScores(quality: 90)->create();

        $rotation = DiamondRotation::factory()->create([
            'month' => '2026-08-01',
            'fiche_id' => $adminPick->id,
            'chosen_via' => 'admin',
            'suggestion_sent_at' => now()->subHour(),
        ]);

        $this->artisan('diamonds:send-rotation-suggestion', ['--force' => true])->assertSuccessful();

        $this->assertSame($adminPick->id, $rotation->fresh()->fiche_id);
        $this->assertSame('admin', $rotation->fresh()->chosen_via);

        // The pick leads the mail even though a higher-scoring candidate exists,
        // because the first candidate is presented as the do-nothing outcome.
        Mail::assertSent(
            DiamondRotationSuggestionMail::class,
            fn (DiamondRotationSuggestionMail $mail) => $mail->candidates->first()->is($adminPick)
        );
    }

    public function test_alerts_admin_when_there_are_no_candidates(): void
    {
        Mail::fake();

        Fiche::factory()->published()->withDiamond()->create();

        $this->artisan('diamonds:send-rotation-suggestion')
            ->expectsOutputToContain('No eligible candidates')
            ->assertSuccessful();

        Mail::assertNotSent(DiamondRotationSuggestionMail::class);
        $this->assertNull(DiamondRotation::forMonth(Carbon::parse('2026-08-01'))->first());
    }

    public function test_suggestion_mail_contains_signed_choice_links_for_backups(): void
    {
        $fiches = Fiche::factory()->count(3)->published()->withScores()->create();

        $this->artisan('diamonds:send-rotation-suggestion')->assertSuccessful();

        $rotation = DiamondRotation::forMonth(Carbon::parse('2026-08-01'))->first();
        $html = (new DiamondRotationSuggestionMail(
            $rotation,
            $rotation->fiche->newQuery()
                ->whereIn('id', $rotation->suggested_fiche_ids)
                ->with(['user', 'initiative'])
                ->withCount(['likes', 'comments'])
                ->get()
        ))->render();

        $this->assertStringContainsString('/diamantjes/wissel/'.$rotation->id.'/', $html);
        $this->assertStringContainsString('signature=', $html);
        $this->assertStringContainsString('Kies deze', $html);
    }
}
