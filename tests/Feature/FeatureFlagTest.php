<?php

namespace Tests\Feature;

use App\Features\DiamantGoals;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_routes_return_404_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $this->get(route('goals.index'))->assertStatus(404);
        $this->get(route('goals.show', 'talent'))->assertStatus(404);
    }

    public function test_goal_routes_return_200_when_feature_enabled(): void
    {
        Feature::define('diamant-goals', true);

        $this->get(route('goals.index'))->assertStatus(200);
        $this->get(route('goals.show', 'talent'))->assertStatus(200);
    }

    public function test_homepage_hides_diamant_section_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('Het DIAMANT-kompas');
        $response->assertDontSee('Zeven doelen om bewoners te laten schitteren');
    }

    public function test_homepage_shows_diamant_section_when_feature_enabled(): void
    {
        Feature::define('diamant-goals', true);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Het DIAMANT-kompas');
    }

    public function test_nav_hides_doelen_dropdown_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('Zeven doelstellingen');
    }

    public function test_nav_shows_doelen_dropdown_when_feature_enabled(): void
    {
        Feature::define('diamant-goals', true);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Zeven doelstellingen');
    }

    public function test_admin_can_view_features_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.features'));

        $response->assertStatus(200);
        $response->assertSee('Feature Flags');
        $response->assertSee('DIAMANT-doelen');
    }

    public function test_non_admin_gets_403_on_features_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.features'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_features_page(): void
    {
        $response = $this->get(route('admin.features'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_toggle_feature_on(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.features.toggle', 'diamant-goals'));

        $response->assertRedirect(route('admin.features'));
        $this->assertTrue(Cache::get(DiamantGoals::CACHE_KEY, false));
    }

    public function test_admin_can_toggle_feature_off(): void
    {
        // Simulate "live" state
        Cache::forever(DiamantGoals::CACHE_KEY, true);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.features.toggle', 'diamant-goals'));

        $response->assertRedirect(route('admin.features'));
        $this->assertFalse(Cache::get(DiamantGoals::CACHE_KEY, false));
        // Admin still sees it via resolver
        Feature::flushCache();
        $this->assertTrue(Feature::for($admin)->active(DiamantGoals::class));
    }

    public function test_unknown_feature_toggle_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.features.toggle', 'non-existent'));

        $response->assertStatus(404);
    }

    public function test_initiative_show_hides_diamant_card_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $initiative = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('Laat het initiatief schitteren');
        $response->assertDontSee('DIAMANT-principes');
    }

    public function test_initiative_show_shows_diamant_card_when_feature_enabled(): void
    {
        Feature::define('diamant-goals', true);

        $initiative = Initiative::factory()->published()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
    }

    public function test_initiatives_index_hides_goal_filter_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertSee('Elk initiatief bundelt praktijkfiches van collega');
        $response->assertSee('Kies een thema en ontdek hoe anderen het aanpakken.');
    }

    public function test_initiatives_index_shows_goal_filter_when_feature_enabled(): void
    {
        Feature::define('diamant-goals', true);

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertSee('Doelen');
    }

    public function test_fiche_van_de_maand_hides_diamant_link_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get(route('fiches.ficheVanDeMaand'));

        $response->assertStatus(200);
        $response->assertDontSee('Het DIAMANT-kompas');
    }

    public function test_fiche_van_de_maand_shows_diamant_link_when_feature_enabled(): void
    {
        Feature::define('diamant-goals', true);

        $response = $this->get(route('fiches.ficheVanDeMaand'));

        $response->assertStatus(200);
        $response->assertSee('Het DIAMANT-kompas');
    }

    public function test_beta_regular_user_cannot_see_goals(): void
    {
        Feature::purge(DiamantGoals::class);

        $user = User::factory()->create();

        $this->assertFalse(Feature::for($user)->active(DiamantGoals::class));
        $this->actingAs($user)->get(route('goals.index'))->assertStatus(404);
    }

    public function test_beta_admin_can_see_goals(): void
    {
        Feature::purge(DiamantGoals::class);

        $admin = User::factory()->admin()->create();

        $this->assertTrue(Feature::for($admin)->active(DiamantGoals::class));
        $this->actingAs($admin)->get(route('goals.index'))->assertStatus(200);
    }

    public function test_live_regular_user_can_see_goals(): void
    {
        Cache::forever(DiamantGoals::CACHE_KEY, true);
        Feature::purge(DiamantGoals::class);

        $user = User::factory()->create();

        $this->assertTrue(Feature::for($user)->active(DiamantGoals::class));
        $this->actingAs($user)->get(route('goals.index'))->assertStatus(200);
    }

    public function test_back_to_beta_regular_user_loses_access(): void
    {
        Cache::forever(DiamantGoals::CACHE_KEY, true);
        Feature::purge(DiamantGoals::class);

        // Now go back to beta
        Cache::forget(DiamantGoals::CACHE_KEY);
        Feature::flushCache();

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse(Feature::for($user)->active(DiamantGoals::class));
        $this->assertTrue(Feature::for($admin)->active(DiamantGoals::class));
    }

    public function test_fiche_edit_preserves_goal_tags_when_feature_disabled(): void
    {
        Feature::define('diamant-goals', false);

        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);
        $goalTag = Tag::factory()->goal()->create(['slug' => 'doel-doen', 'name' => 'Doen']);
        $themeTag = Tag::factory()->theme()->create();
        $fiche->tags()->attach([$goalTag->id, $themeTag->id]);

        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\FicheEdit::class, ['fiche' => $fiche])
            ->set('title', 'Updated Title')
            ->set('selectedThemeTags', [$themeTag->id])
            ->call('save');

        $fiche->refresh();
        $this->assertTrue($fiche->tags->contains($goalTag));
        $this->assertTrue($fiche->tags->contains($themeTag));
    }
}
