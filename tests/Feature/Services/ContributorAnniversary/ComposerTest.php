<?php

namespace Tests\Feature\Services\ContributorAnniversary;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use App\Services\ContributorAnniversary\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_fiche_title_is_earliest_published(): void
    {
        $user = User::factory()->create();
        $first = Fiche::factory()->for($user)->create(['published' => true, 'title' => 'Eerste', 'created_at' => now()->subYears(5)]);
        Fiche::factory()->for($user)->create(['published' => true, 'title' => 'Latere', 'created_at' => now()->subYear()]);

        $payload = (new Composer)->compose($user);

        $this->assertSame('Eerste', $payload->firstFicheTitle);
    }

    public function test_first_fiche_ignores_unpublished(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->for($user)->create(['published' => false, 'title' => 'Concept', 'created_at' => now()->subYears(6)]);
        Fiche::factory()->for($user)->create(['published' => true, 'title' => 'Gepubliceerd', 'created_at' => now()->subYears(5)]);

        $payload = (new Composer)->compose($user);

        $this->assertSame('Gepubliceerd', $payload->firstFicheTitle);
    }

    public function test_first_fiche_theme_comes_from_theme_tag(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['published' => true, 'created_at' => now()->subYears(5)]);
        $goal = Tag::factory()->goal()->create(['name' => 'Autonomie']);
        $theme = Tag::factory()->theme()->create(['name' => 'samen koken']);
        $fiche->tags()->attach([$goal->id, $theme->id]);

        $payload = (new Composer)->compose($user);

        $this->assertSame('samen koken', $payload->firstFicheTheme);
    }

    public function test_theme_is_null_when_first_fiche_has_no_theme_tag(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['published' => true, 'created_at' => now()->subYears(5)]);
        $fiche->tags()->attach(Tag::factory()->goal()->create()->id);

        $payload = (new Composer)->compose($user);

        $this->assertSame($fiche->title, $payload->firstFicheTitle);
        $this->assertNull($payload->firstFicheTheme);
    }

    public function test_first_fiche_initiative_name_and_slug_are_surfaced(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create(['title' => 'Samen koken', 'slug' => 'samen-koken']);
        Fiche::factory()->for($user)->for($initiative)->create(['published' => true, 'created_at' => now()->subYears(5)]);

        $payload = (new Composer)->compose($user);

        $this->assertSame('Samen koken', $payload->firstFicheInitiativeName);
        $this->assertSame('samen-koken', $payload->firstFicheInitiativeSlug);
    }

    public function test_null_when_no_published_fiche(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->for($user)->create(['published' => false]);

        $payload = (new Composer)->compose($user);

        $this->assertNull($payload->firstFicheTitle);
        $this->assertNull($payload->firstFicheTheme);
        $this->assertNull($payload->firstFicheInitiativeName);
        $this->assertNull($payload->firstFicheInitiativeSlug);
    }
}
