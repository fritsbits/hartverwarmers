<?php

namespace Tests\Feature;

use App\Models\Elaboration;
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
        $tag = Tag::factory()->interest()->create();
        $tagged = Initiative::factory()->published()->create();
        $tagged->tags()->attach($tag);
        $untagged = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.index', ['tag' => $tag->slug]));

        $response->assertStatus(200);
        $response->assertSee($tagged->title);
        $response->assertDontSee($untagged->title);
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

    public function test_initiative_show_displays_published_elaborations(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $published = Elaboration::factory()->published()->create(['initiative_id' => $initiative->id]);
        $unpublished = Elaboration::factory()->create(['initiative_id' => $initiative->id, 'published' => false]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee($published->title);
        $response->assertDontSee($unpublished->title);
    }

    public function test_elaboration_show_page_loads(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $elaboration = Elaboration::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('elaborations.show', [$initiative, $elaboration]));

        $response->assertStatus(200);
        $response->assertSee($elaboration->title);
    }

    public function test_elaboration_show_returns_404_for_unpublished(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $elaboration = Elaboration::factory()->create(['initiative_id' => $initiative->id, 'published' => false]);

        $response = $this->get(route('elaborations.show', [$initiative, $elaboration]));

        $response->assertStatus(404);
    }

    public function test_elaboration_print_page_loads(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $elaboration = Elaboration::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('elaborations.print', [$initiative, $elaboration]));

        $response->assertStatus(200);
        $response->assertSee($elaboration->title);
    }

    public function test_bookmark_toggle_creates_and_removes_bookmark(): void
    {
        $user = User::factory()->create();
        $elaboration = Elaboration::factory()->published()->create();

        // Create bookmark
        $response = $this->actingAs($user)->post(route('elaborations.bookmark', $elaboration));
        $response->assertRedirect();
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Elaboration::class,
            'likeable_id' => $elaboration->id,
            'type' => 'bookmark',
        ]);

        // Remove bookmark
        $response = $this->actingAs($user)->post(route('elaborations.bookmark', $elaboration));
        $response->assertRedirect();
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_type' => Elaboration::class,
            'likeable_id' => $elaboration->id,
            'type' => 'bookmark',
        ]);
    }

    public function test_bookmark_requires_authentication(): void
    {
        $elaboration = Elaboration::factory()->published()->create();

        $response = $this->post(route('elaborations.bookmark', $elaboration));

        $response->assertRedirect(route('login'));
    }

    public function test_comment_store_creates_comment(): void
    {
        $user = User::factory()->create();
        $elaboration = Elaboration::factory()->published()->create();

        $response = $this->actingAs($user)->post(route('elaborations.comment', $elaboration), [
            'body' => 'Geweldige uitwerking!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Elaboration::class,
            'commentable_id' => $elaboration->id,
            'body' => 'Geweldige uitwerking!',
        ]);
    }

    public function test_comment_requires_authentication(): void
    {
        $elaboration = Elaboration::factory()->published()->create();

        $response = $this->post(route('elaborations.comment', $elaboration), [
            'body' => 'Test',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_comment_validates_body_required(): void
    {
        $user = User::factory()->create();
        $elaboration = Elaboration::factory()->published()->create();

        $response = $this->actingAs($user)->post(route('elaborations.comment', $elaboration), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_contributors_index_shows_users_with_elaborations(): void
    {
        $contributor = User::factory()->create();
        Elaboration::factory()->create(['user_id' => $contributor->id]);

        $userWithoutElaborations = User::factory()->create();

        $response = $this->get(route('contributors.index'));

        $response->assertStatus(200);
        $response->assertSee($contributor->name);
        $response->assertDontSee($userWithoutElaborations->name);
    }

    public function test_contributor_show_page_loads(): void
    {
        $contributor = User::factory()->create();
        Elaboration::factory()->published()->create(['user_id' => $contributor->id]);

        $response = $this->get(route('contributors.show', $contributor));

        $response->assertStatus(200);
        $response->assertSee($contributor->name);
    }

    public function test_themes_index_shows_placeholder(): void
    {
        $response = $this->get(route('themes.index'));

        $response->assertStatus(200);
        $response->assertSee('In opbouw');
    }

    public function test_initiative_show_highlights_diamond_elaboration(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $diamond = Elaboration::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);
        $regular = Elaboration::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Diamantje');
        $response->assertSee($diamond->title);
        $response->assertSee($regular->title);
    }

    public function test_elaboration_show_displays_related_elaborations(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $elaboration = Elaboration::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        $related = Elaboration::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('elaborations.show', [$initiative, $elaboration]));

        $response->assertStatus(200);
        $response->assertSee('Gerelateerde uitwerkingen');
        $response->assertSee($related->title);
    }

    public function test_elaboration_show_displays_diamond_badge(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $elaboration = Elaboration::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('elaborations.show', [$initiative, $elaboration]));

        $response->assertStatus(200);
        $response->assertSee('Diamantje');
    }

    public function test_profile_bookmarks_shows_bookmarked_elaborations(): void
    {
        $user = User::factory()->create();
        $elaboration = Elaboration::factory()->published()->create();

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Elaboration::class,
            'likeable_id' => $elaboration->id,
            'type' => 'bookmark',
        ]);

        $response = $this->actingAs($user)->get(route('profile.bookmarks'));

        $response->assertStatus(200);
        $response->assertSee($elaboration->title);
    }

    public function test_initiative_show_displays_diamant_profile(): void
    {
        $initiative = Initiative::factory()->published()->withDiamantGuidance()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Doen');
        $response->assertSee('Inclusief');
        $response->assertSee('Autonomie');
        $response->assertSee('Mensgericht');
        $response->assertSee('Anderen');
        $response->assertSee('Normalisatie');
        $response->assertSee('Talent');
        $response->assertSee('Van '.$initiative->title.' naar diamantje');
    }

    public function test_initiative_show_displays_contributor_count(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Elaboration::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user1->id]);
        Elaboration::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user2->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Uitgevoerd door 2 begeleiders');
    }

    public function test_initiative_show_displays_related_initiatives(): void
    {
        $tag = Tag::factory()->interest()->create();
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
        $response->assertSee('Collega-begeleiders delen hun ervaringen met '.$initiative->title.'.');
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
