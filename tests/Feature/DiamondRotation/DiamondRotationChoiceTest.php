<?php

namespace Tests\Feature\DiamondRotation;

use App\Models\DiamondRotation;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DiamondRotationChoiceTest extends TestCase
{
    use RefreshDatabase;

    private function signedChoiceUrl(DiamondRotation $rotation, Fiche $fiche): string
    {
        return URL::temporarySignedRoute('diamond-rotation.choose', now()->addDay(), [
            'rotation' => $rotation->id,
            'fiche' => $fiche->id,
        ]);
    }

    public function test_choice_page_requires_a_valid_signature(): void
    {
        $rotation = DiamondRotation::factory()->create();

        $this->get(route('diamond-rotation.choose', [
            'rotation' => $rotation->id,
            'fiche' => $rotation->fiche_id,
        ]))->assertForbidden();
    }

    public function test_choice_page_shows_confirmation_for_a_backup_candidate(): void
    {
        $backup = Fiche::factory()->published()->create();
        $rotation = DiamondRotation::factory()->create();
        $rotation->update(['suggested_fiche_ids' => [$rotation->fiche_id, $backup->id]]);

        $this->get($this->signedChoiceUrl($rotation, $backup))
            ->assertOk()
            ->assertSee($backup->title)
            ->assertSee('Bevestig deze keuze');
    }

    public function test_choice_page_rejects_a_fiche_that_was_not_suggested(): void
    {
        $rotation = DiamondRotation::factory()->create();
        $unrelated = Fiche::factory()->published()->create();

        $this->get($this->signedChoiceUrl($rotation, $unrelated))->assertNotFound();
    }

    public function test_confirming_updates_the_pick(): void
    {
        $backup = Fiche::factory()->published()->create();
        $rotation = DiamondRotation::factory()->create();
        $rotation->update(['suggested_fiche_ids' => [$rotation->fiche_id, $backup->id]]);

        $url = $this->signedChoiceUrl($rotation, $backup);

        $this->post($url)->assertRedirect($url);

        $rotation->refresh();
        $this->assertSame($backup->id, $rotation->fiche_id);
        $this->assertSame('admin', $rotation->chosen_via);

        $this->get($url)
            ->assertOk()
            ->assertSee('Je hoeft verder niets te doen');
    }

    public function test_confirming_does_nothing_once_the_diamond_is_awarded(): void
    {
        $backup = Fiche::factory()->published()->create();
        $rotation = DiamondRotation::factory()->awarded()->create();
        $originalPick = $rotation->fiche_id;
        $rotation->update(['suggested_fiche_ids' => [$originalPick, $backup->id]]);

        $this->post($this->signedChoiceUrl($rotation, $backup))->assertRedirect();

        $this->assertSame($originalPick, $rotation->fresh()->fiche_id);

        $this->get($this->signedChoiceUrl($rotation, $backup))
            ->assertOk()
            ->assertSee('al toegekend');
    }

    public function test_current_pick_shows_planned_state_without_form(): void
    {
        $rotation = DiamondRotation::factory()->create();

        $this->get($this->signedChoiceUrl($rotation, $rotation->fiche))
            ->assertOk()
            ->assertSee('Je hoeft verder niets te doen')
            ->assertDontSee('Bevestig deze keuze');
    }
}
