<?php

namespace Tests\Feature\Admin;

use App\Mail\DiamondRotationSuggestionMail;
use App\Models\DiamondRotation;
use App\Models\Fiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminDiamondRotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.admin_address' => 'admin@example.com']);
        Carbon::setTestNow('2026-07-15 10:00:00');
    }

    public function test_page_requires_admin(): void
    {
        $this->get(route('admin.diamond-rotations'))->assertRedirect(route('login'));

        $contributor = User::factory()->create(['role' => 'contributor']);
        $this->actingAs($contributor)->get(route('admin.diamond-rotations'))->assertForbidden();

        $curator = User::factory()->create(['role' => 'curator']);
        $this->actingAs($curator)->get(route('admin.diamond-rotations'))->assertForbidden();
    }

    public function test_admin_can_view_the_rotation_overview(): void
    {
        $admin = User::factory()->admin()->create();
        $diamond = Fiche::factory()->published()->withDiamond()->create();
        Fiche::factory()->published()->withScores()->create();

        $this->actingAs($admin)
            ->get(route('admin.diamond-rotations'))
            ->assertOk()
            ->assertSee('Diamantje van de maand')
            ->assertSee($diamond->title)
            ->assertSee('Volgende maand');
    }

    public function test_admin_can_set_the_pick_for_next_month(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = Fiche::factory()->published()->create();

        $this->actingAs($admin)
            ->post(route('admin.diamond-rotations.choose'), ['fiche_id' => $fiche->id])
            ->assertRedirect(route('admin.diamond-rotations'));

        $rotation = DiamondRotation::forMonth(Carbon::parse('2026-08-01'))->first();

        $this->assertNotNull($rotation);
        $this->assertSame($fiche->id, $rotation->fiche_id);
        $this->assertSame('admin', $rotation->chosen_via);
        $this->assertContains($fiche->id, $rotation->suggested_fiche_ids);
    }

    public function test_choosing_overrides_an_existing_auto_pick(): void
    {
        $admin = User::factory()->admin()->create();
        $autoPick = Fiche::factory()->published()->create();
        $newPick = Fiche::factory()->published()->create();

        $rotation = DiamondRotation::factory()->create([
            'month' => '2026-08-01',
            'fiche_id' => $autoPick->id,
            'suggested_fiche_ids' => [$autoPick->id],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.diamond-rotations.choose'), ['fiche_id' => $newPick->id])
            ->assertRedirect();

        $rotation->refresh();
        $this->assertSame($newPick->id, $rotation->fiche_id);
        $this->assertSame('admin', $rotation->chosen_via);
        $this->assertSame([$autoPick->id, $newPick->id], $rotation->suggested_fiche_ids);
    }

    public function test_cannot_pick_an_unpublished_fiche_or_existing_diamond(): void
    {
        $admin = User::factory()->admin()->create();
        $unpublished = Fiche::factory()->create(['published' => false]);
        $alreadyDiamond = Fiche::factory()->published()->withDiamond()->create();

        $this->actingAs($admin)
            ->post(route('admin.diamond-rotations.choose'), ['fiche_id' => $unpublished->id])
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.diamond-rotations.choose'), ['fiche_id' => $alreadyDiamond->id])
            ->assertNotFound();

        $this->assertNull(DiamondRotation::forMonth(Carbon::parse('2026-08-01'))->first());
    }

    public function test_admin_can_resend_the_suggestion_mail(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        Fiche::factory()->published()->withScores()->create();

        $this->actingAs($admin)
            ->post(route('admin.diamond-rotations.send-suggestion'))
            ->assertRedirect(route('admin.diamond-rotations'));

        Mail::assertSent(DiamondRotationSuggestionMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));
    }
}
