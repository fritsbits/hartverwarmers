<?php

namespace Tests\Feature\Models;

use App\Models\Fiche;
use App\Models\Theme;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheThemeLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_attach_fiche_to_theme(): void
    {
        $theme = Theme::factory()->create();
        $fiche = Fiche::factory()->create();

        $theme->fiches()->attach($fiche);

        $this->assertTrue($theme->fresh()->fiches->contains($fiche));
        $this->assertTrue($fiche->fresh()->themes->contains($theme));
    }

    public function test_pivot_is_unique_per_pair(): void
    {
        $theme = Theme::factory()->create();
        $fiche = Fiche::factory()->create();

        $theme->fiches()->attach($fiche);

        $this->expectException(QueryException::class);
        $theme->fiches()->attach($fiche);
    }

    public function test_deleting_theme_cascades_to_pivot(): void
    {
        $theme = Theme::factory()->create();
        $fiche = Fiche::factory()->create();
        $theme->fiches()->attach($fiche);

        $theme->delete();

        $this->assertDatabaseMissing('fiche_theme', ['fiche_id' => $fiche->id]);
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id]);
    }
}
