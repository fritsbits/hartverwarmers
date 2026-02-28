<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiatives_index_displays_published_initiatives(): void
    {
        $published = Initiative::factory()->published()->create();
        $unpublished = Initiative::factory()->create(['published' => false]);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertSee($published->title);
        $response->assertDontSee($unpublished->title);
    }

    public function test_initiatives_index_filters_by_tag(): void
    {
        $tag = Tag::factory()->theme()->create();
        $tagged = Initiative::factory()->published()->create();
        $tagged->tags()->attach($tag);
        $untagged = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.index', ['tag' => $tag->slug]));

        $response->assertStatus(200);
        $response->assertSee($tagged->title);
        $response->assertDontSee($untagged->title);
    }

    public function test_initiatives_index_filters_by_search(): void
    {
        $matching = Initiative::factory()->published()->create(['title' => 'Muziektherapie voor senioren']);
        $nonMatching = Initiative::factory()->published()->create(['title' => 'Wandelen in het park']);

        $response = $this->get(route('initiatives.index', ['search' => 'Muziek']));

        $response->assertStatus(200);
        $response->assertSee($matching->title);
        $response->assertDontSee($nonMatching->title);
    }

    public function test_initiatives_index_search_and_tag_combined(): void
    {
        $tag = Tag::factory()->theme()->create();
        $matchesBoth = Initiative::factory()->published()->create(['title' => 'Muziektherapie']);
        $matchesBoth->tags()->attach($tag);
        $matchesSearchOnly = Initiative::factory()->published()->create(['title' => 'Muziekles']);
        $matchesTagOnly = Initiative::factory()->published()->create(['title' => 'Wandelen']);
        $matchesTagOnly->tags()->attach($tag);

        $response = $this->get(route('initiatives.index', ['search' => 'Muziek', 'tag' => $tag->slug]));

        $response->assertStatus(200);
        $response->assertSee($matchesBoth->title);
        $response->assertDontSee($matchesSearchOnly->title);
        $response->assertDontSee($matchesTagOnly->title);
    }

    public function test_initiatives_index_search_matches_description(): void
    {
        $matching = Initiative::factory()->published()->create([
            'title' => 'Activiteit A',
            'description' => 'Een leuke knutselmiddag voor bewoners',
        ]);
        $nonMatching = Initiative::factory()->published()->create([
            'title' => 'Activiteit B',
            'description' => 'Wandelen door de tuin',
        ]);

        $response = $this->get(route('initiatives.index', ['search' => 'knutsel']));

        $response->assertStatus(200);
        $response->assertSee($matching->title);
        $response->assertDontSee($nonMatching->title);
    }

    public function test_initiative_show_displays_published_initiative(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee($initiative->title);
    }

    public function test_initiative_show_returns_404_for_unpublished(): void
    {
        $initiative = Initiative::factory()->create(['published' => false]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(404);
    }

    public function test_initiative_show_displays_published_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $published = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $unpublished = Fiche::factory()->create(['initiative_id' => $initiative->id, 'published' => false]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee($published->title);
        $response->assertDontSee($unpublished->title);
    }

    public function test_fiche_show_page_loads(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee($fiche->title);
    }

    public function test_fiche_show_returns_404_for_unpublished(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->create(['initiative_id' => $initiative->id, 'published' => false]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(404);
    }

    public function test_bookmark_toggle_creates_and_removes_bookmark(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        // Create bookmark
        $response = $this->actingAs($user)->post(route('fiches.bookmark', $fiche));
        $response->assertRedirect();
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        // Remove bookmark
        $response = $this->actingAs($user)->post(route('fiches.bookmark', $fiche));
        $response->assertRedirect();
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);
    }

    public function test_bookmark_requires_authentication(): void
    {
        $fiche = Fiche::factory()->published()->create();

        $response = $this->post(route('fiches.bookmark', $fiche));

        $response->assertRedirect(route('login'));
    }

    public function test_comment_store_creates_comment(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        $response = $this->actingAs($user)->post(route('fiches.comment', $fiche), [
            'body' => 'Geweldige fiche!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Geweldige fiche!',
        ]);
    }

    public function test_comment_requires_authentication(): void
    {
        $fiche = Fiche::factory()->published()->create();

        $response = $this->post(route('fiches.comment', $fiche), [
            'body' => 'Test',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_comment_validates_body_required(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        $response = $this->actingAs($user)->post(route('fiches.comment', $fiche), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_contributors_index_shows_users_with_fiches(): void
    {
        $contributor = User::factory()->create();
        Fiche::factory()->create(['user_id' => $contributor->id]);

        $userWithoutFiches = User::factory()->create();

        $response = $this->get(route('contributors.index'));

        $response->assertStatus(200);
        $response->assertSee($contributor->full_name);
        $response->assertDontSee($userWithoutFiches->full_name);
    }

    public function test_contributor_show_page_loads(): void
    {
        $contributor = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $contributor->id]);

        $response = $this->get(route('contributors.show', $contributor));

        $response->assertStatus(200);
        $response->assertSee($contributor->full_name);
    }

    public function test_themes_index_shows_placeholder(): void
    {
        $response = $this->get(route('themes.index'));

        $response->assertStatus(200);
        $response->assertSee('In opbouw');
    }

    public function test_initiative_show_highlights_diamond_fiche(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $diamond = Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);
        $regular = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Diamantje');
        $response->assertSee($diamond->title);
        $response->assertSee($regular->title);
    }

    public function test_fiche_show_displays_other_fiches_from_initiative(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        $related = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Meer fiches');
        $response->assertSee($related->title);
    }

    public function test_fiche_show_displays_diamond_badge(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Diamantje');
    }

    public function test_profile_bookmarks_shows_bookmarked_fiches(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        $response = $this->actingAs($user)->get(route('profile.bookmarks'));

        $response->assertStatus(200);
        $response->assertSee($fiche->title);
    }

    public function test_initiative_show_does_not_display_diamant_profile(): void
    {
        $initiative = Initiative::factory()->published()->withDiamantGuidance()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('Van '.$initiative->title.' naar diamantje');
    }

    public function test_initiative_show_displays_fiches_section_heading_and_fiche_titles(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $fiche1 = Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user1->id]);
        $fiche2 = Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user2->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee("Fiches door collega's", false);
        $response->assertSee($fiche1->title);
        $response->assertSee($fiche2->title);
        $response->assertDontSee('keer ervaringen gedeeld');
    }

    public function test_initiative_show_displays_expand_button_when_more_than_six_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(8)->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Alle 8 fiches tonen');
    }

    public function test_initiative_show_hides_expand_button_when_six_or_fewer_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(5)->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('fiches tonen');
    }

    public function test_initiative_show_displays_comment_count_when_comments_exist(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        $initiative->comments()->create(['user_id' => $user->id, 'body' => 'Geweldig initiatief!']);
        $initiative->comments()->create(['user_id' => $user->id, 'body' => 'Werkt heel goed.']);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('2 keer ervaringen gedeeld');
    }

    public function test_initiative_show_displays_related_initiatives(): void
    {
        $tag = Tag::factory()->theme()->create();
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($tag);
        $related = Initiative::factory()->published()->create();
        $related->tags()->attach($tag);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Gerelateerde initiatieven');
        $response->assertSee($related->title);
    }

    public function test_initiative_show_displays_community_section(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Vertel, hoe ging het bij jullie?');
        $response->assertSee('om je ervaring te delen.');
    }

    public function test_initiative_show_displays_empty_encouragement_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $response = $this->actingAs($user)->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Vertel, hoe ging het bij jullie?');
        $response->assertSee('Wees de eerste die een ervaring deelt.');
    }

    public function test_initiative_comment_store_creates_comment(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $response = $this->actingAs($user)->post(route('initiatives.comment', $initiative), [
            'body' => 'Geweldig initiatief!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Initiative::class,
            'commentable_id' => $initiative->id,
            'body' => 'Geweldig initiatief!',
        ]);
    }
}
