<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
        $response->assertViewHas('initiatives', fn ($initiatives) => $initiatives instanceof Collection);
    }

    public function test_initiatives_index_provides_discover_order(): void
    {
        // Rich initiative (10+ fiches)
        $rich = Initiative::factory()->published()->create(['title' => 'Bingo']);
        Fiche::factory()->published()->count(12)->create(['initiative_id' => $rich->id]);

        // Growing initiative (3-9 fiches)
        $growing = Initiative::factory()->published()->create(['title' => 'Koken']);
        Fiche::factory()->published()->count(5)->create(['initiative_id' => $growing->id]);

        // Needs-love initiative (0-2 fiches)
        $needsLove = Initiative::factory()->published()->create(['title' => 'Yoga']);
        Fiche::factory()->published()->count(1)->create(['initiative_id' => $needsLove->id]);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $discoverOrder = $response->viewData('discoverOrder');
        $this->assertIsArray($discoverOrder);
        // Rich initiative should come first in discover order
        $this->assertEquals($rich->id, $discoverOrder[0]);
    }

    public function test_initiatives_index_passes_goal_data(): void
    {
        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertViewHas('goals');
        $goals = $response->viewData('goals');
        $this->assertCount(7, $goals);
        $this->assertArrayHasKey('slug', $goals[0]);
        $this->assertArrayHasKey('tagSlug', $goals[0]);
        $this->assertArrayHasKey('letter', $goals[0]);
        $this->assertArrayHasKey('keyword', $goals[0]);
        $this->assertArrayHasKey('description', $goals[0]);
    }

    public function test_initiatives_index_eager_loads_goal_tags(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $goalTag = Tag::factory()->goal()->create(['slug' => 'doel-doen']);
        $themeTag = Tag::factory()->theme()->create();
        $initiative->tags()->attach([$goalTag->id, $themeTag->id]);

        $response = $this->get(route('initiatives.index'));

        $loadedTags = $response->viewData('initiatives')->first()->tags;
        $this->assertTrue($loadedTags->contains('id', $goalTag->id));
        $this->assertFalse($loadedTags->contains('id', $themeTag->id));
    }

    public function test_initiatives_index_includes_published_fiche_count(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(3)->create(['initiative_id' => $initiative->id]);
        Fiche::factory()->create(['initiative_id' => $initiative->id, 'published' => false]);

        $response = $this->get(route('initiatives.index'));

        $this->assertEquals(3, $response->viewData('initiatives')->first()->fiches_count);
    }

    public function test_initiatives_index_shows_trending_initiative(): void
    {
        $trending = Initiative::factory()->published()->create(['title' => 'Trending']);
        Fiche::factory()->published()->count(3)->create([
            'initiative_id' => $trending->id,
            'created_at' => now()->subDays(10),
        ]);

        $other = Initiative::factory()->published()->create(['title' => 'Other']);
        Fiche::factory()->published()->count(1)->create([
            'initiative_id' => $other->id,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertViewHas('trending');
        $trendingResult = $response->viewData('trending');
        $this->assertNotNull($trendingResult);
        $this->assertEquals($trending->id, $trendingResult->id);
    }

    public function test_initiatives_index_no_trending_when_no_recent_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(3)->create([
            'initiative_id' => $initiative->id,
            'created_at' => now()->subDays(90),
        ]);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('trending'));
    }

    public function test_initiatives_index_provides_needs_love_initiatives(): void
    {
        $rich = Initiative::factory()->published()->create(['title' => 'Veel fiches']);
        Fiche::factory()->published()->count(10)->create(['initiative_id' => $rich->id]);

        $needsLove = Initiative::factory()->published()->create(['title' => 'Weinig fiches']);
        Fiche::factory()->published()->count(1)->create(['initiative_id' => $needsLove->id]);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $needsLoveInitiatives = $response->viewData('needsLoveInitiatives');
        $titles = array_column($needsLoveInitiatives, 'title');
        $this->assertContains('Weinig fiches', $titles);
        $this->assertNotContains('Veel fiches', $titles);
    }

    public function test_initiatives_index_loads_latest_fiche_at(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $latestFiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'created_at' => now()->subDays(5),
        ]);
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'created_at' => now()->subDays(30),
        ]);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $loadedInitiative = $response->viewData('initiatives')->first();
        $this->assertNotNull($loadedInitiative->latest_fiche_at);
        $this->assertEquals(
            $latestFiche->created_at->startOfSecond()->toDateTimeString(),
            \Carbon\Carbon::parse($loadedInitiative->latest_fiche_at)->startOfSecond()->toDateTimeString()
        );
    }

    public function test_initiatives_index_returns_all_without_pagination(): void
    {
        Initiative::factory()->published()->count(15)->create();

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $initiatives = $response->viewData('initiatives');
        $this->assertInstanceOf(Collection::class, $initiatives);
        $this->assertCount(15, $initiatives);
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
        Fiche::factory()->published()->create(['user_id' => $contributor->id]);

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
        $response->assertSee("uitwerkingen door collega's", false);
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
        $response->assertSee('+ 2 meer');
    }

    public function test_initiative_show_hides_expand_button_when_six_or_fewer_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(5)->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('fiche-list-expand');
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

    public function test_initiative_show_does_not_display_community_section_when_hidden(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('Vertel, hoe ging het bij jullie?');
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

    public function test_initiative_show_displays_diamant_analyse_for_known_slug(): void
    {
        $initiative = Initiative::factory()->published()->create(['slug' => 'quiz']);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Laat het initiatief schitteren');
        $response->assertSee('Haal meer uit dit initiatief door DIAMANT-principes toe te passen.');
        $response->assertSee('Doen');
        $response->assertSee('Bewoners zijn actief bezig met nadenken en overleggen.');
    }

    public function test_initiative_show_displays_multiple_diamant_goals(): void
    {
        $initiative = Initiative::factory()->published()->create(['slug' => 'quiz']);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Doen');
        $response->assertSee('Anderen');
        $response->assertSee('Talent');
        $response->assertSee('Inclusief');
    }

    public function test_initiative_show_hides_diamant_card_for_unknown_slug_without_image(): void
    {
        $initiative = Initiative::factory()->published()->create([
            'slug' => 'onbekend-initiatief',
            'image' => null,
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('Laat het initiatief schitteren');
    }

    public function test_initiative_show_displays_card_with_image_but_no_analyse(): void
    {
        $initiative = Initiative::factory()->published()->create([
            'slug' => 'onbekend-initiatief',
            'image' => '/img/initiatives/test.webp',
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Laat het initiatief schitteren');
    }

    public function test_thumbnail_url_derives_from_image_path(): void
    {
        $initiative = Initiative::factory()->published()->create([
            'image' => '/img/initiatives/bingo.webp',
        ]);

        $this->assertEquals('/img/initiatives/bingo-thumb.webp', $initiative->thumbnailUrl());
    }

    public function test_thumbnail_url_returns_null_when_no_image(): void
    {
        $initiative = Initiative::factory()->published()->create(['image' => null]);

        $this->assertNull($initiative->thumbnailUrl());
    }
}
