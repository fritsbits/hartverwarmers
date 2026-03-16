<?php

namespace Tests\Feature\Pages;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContributorIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_contributors_page_loads(): void
    {
        $response = $this->get(route('contributors.index'));

        $response->assertStatus(200);
        $response->assertSee('Samen maken we het verschil');
    }

    public function test_only_users_with_published_fiches_are_shown(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $userWithFiche = User::factory()->create(['first_name' => 'Zichtbaar', 'last_name' => 'Persoon']);
        Fiche::factory()->published()->create(['user_id' => $userWithFiche->id, 'initiative_id' => $initiative->id]);

        $userWithoutFiche = User::factory()->create(['first_name' => 'Onzichtbaar', 'last_name' => 'Persoon']);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Zichtbaar Persoon')
            ->assertDontSee('Onzichtbaar Persoon');
    }

    public function test_fiche_count_is_displayed(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'Bijdrager']);
        Fiche::factory()->published()->count(3)->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Test Bijdrager')
            ->assertSeeHtml('>3</span>');
    }

    public function test_initiative_context_is_shown_on_card(): void
    {
        $initiative = Initiative::factory()->published()->create(['title' => 'Muziektherapie']);
        $user = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Muziektherapie');
    }

    public function test_search_filters_by_name(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $jan = User::factory()->create(['first_name' => 'Jan', 'last_name' => 'Peeters']);
        Fiche::factory()->published()->create(['user_id' => $jan->id, 'initiative_id' => $initiative->id]);

        $an = User::factory()->create(['first_name' => 'An', 'last_name' => 'Janssens']);
        Fiche::factory()->published()->create(['user_id' => $an->id, 'initiative_id' => $initiative->id]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->set('search', 'Peeters')
            ->assertSee('Jan Peeters')
            ->assertDontSee('An Janssens');
    }

    public function test_search_filters_by_organisation(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $user1 = User::factory()->create(['first_name' => 'Test', 'last_name' => 'Eén', 'organisation' => 'WZC Pniel']);
        Fiche::factory()->published()->create(['user_id' => $user1->id, 'initiative_id' => $initiative->id]);

        $user2 = User::factory()->create(['first_name' => 'Test', 'last_name' => 'Twee', 'organisation' => 'Den Bogaet']);
        Fiche::factory()->published()->create(['user_id' => $user2->id, 'initiative_id' => $initiative->id]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->set('search', 'Pniel')
            ->assertSee('Test Eén')
            ->assertDontSee('Test Twee');
    }

    public function test_guest_sees_cta_block(): void
    {
        $response = $this->get(route('contributors.index'));

        $response->assertStatus(200);
        $response->assertSee('Word ook bijdrager');
        $response->assertSee('Registreer');
    }

    public function test_authenticated_user_sees_write_cta_instead_of_register(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('contributors.index'));

        $response->assertStatus(200);
        $response->assertSee('Schrijf een fiche');
        $response->assertDontSee('Registreer');
    }

    public function test_contributor_show_page_still_works(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee($user->full_name);
    }

    public function test_show_page_displays_stats(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        Fiche::factory()->published()->count(3)->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'kudos_count' => 2,
        ]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('fiches');
        $response->assertSee('kudos');
        $response->assertSee('Lid sinds '.$user->created_at->format('Y'));
    }

    public function test_show_page_groups_fiches_by_initiative(): void
    {
        $initiative1 = Initiative::factory()->published()->create(['title' => 'Muziektherapie']);
        $initiative2 = Initiative::factory()->published()->create(['title' => 'Creatief atelier']);
        $user = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative1->id]);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative2->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('Muziektherapie');
        $response->assertSee('Creatief atelier');
    }

    public function test_show_page_has_back_link_to_contributors(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('Bekijk alle bijdragers');
    }

    public function test_show_page_displays_social_links(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create([
            'website' => 'https://example.com',
            'linkedin' => 'https://linkedin.com/in/test',
        ]);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('Website');
        $response->assertSee('LinkedIn');
    }

    public function test_stats_are_displayed_in_hero(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        Fiche::factory()->published()->count(2)->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('bijdragers')
            ->assertSee('organisaties')
            ->assertSee('fiches');
    }

    public function test_recently_active_section_shows_contributors(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $recent = User::factory()->create(['first_name' => 'Recent', 'last_name' => 'Actief']);
        Fiche::factory()->published()->create([
            'user_id' => $recent->id,
            'initiative_id' => $initiative->id,
            'created_at' => now()->subDay(),
        ]);

        $older = User::factory()->create(['first_name' => 'Ouder', 'last_name' => 'Actief']);
        Fiche::factory()->published()->create([
            'user_id' => $older->id,
            'initiative_id' => $initiative->id,
            'created_at' => now()->subMonth(),
        ]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Wie deelde er recent?')
            ->assertSee('Recent Actief')
            ->assertSee('Ouder Actief');
    }

    public function test_newcomers_section_shows_single_fiche_users(): void
    {
        $initiative = Initiative::factory()->published()->create();

        // Create 3 newcomers (minimum to show section)
        for ($i = 1; $i <= 3; $i++) {
            $newcomer = User::factory()->create(['first_name' => "Nieuwkomer{$i}", 'last_name' => 'Test']);
            Fiche::factory()->published()->create([
                'user_id' => $newcomer->id,
                'initiative_id' => $initiative->id,
            ]);
        }

        // User with 2 fiches should NOT appear in newcomers
        $experienced = User::factory()->create(['first_name' => 'Ervaren', 'last_name' => 'Test']);
        Fiche::factory()->published()->count(2)->create([
            'user_id' => $experienced->id,
            'initiative_id' => $initiative->id,
        ]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Welkom, nieuwe bijdragers')
            ->assertSee('Nieuwkomer1 Test')
            ->assertSee('Nieuwkomer2 Test')
            ->assertSee('Nieuwkomer3 Test');
    }

    public function test_newcomers_section_hidden_when_fewer_than_three(): void
    {
        $initiative = Initiative::factory()->published()->create();

        // Only 2 newcomers — section should be hidden
        for ($i = 1; $i <= 2; $i++) {
            $newcomer = User::factory()->create(['first_name' => "Nieuwkomer{$i}", 'last_name' => 'Test']);
            Fiche::factory()->published()->create([
                'user_id' => $newcomer->id,
                'initiative_id' => $initiative->id,
            ]);
        }

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertDontSee('Welkom, nieuwe bijdragers');
    }

    public function test_top_contributors_ordered_by_fiche_count(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $top = User::factory()->create(['first_name' => 'Top', 'last_name' => 'Bijdrager']);
        Fiche::factory()->published()->count(5)->create([
            'user_id' => $top->id,
            'initiative_id' => $initiative->id,
        ]);

        $lesser = User::factory()->create(['first_name' => 'Minder', 'last_name' => 'Bijdrager']);
        Fiche::factory()->published()->count(2)->create([
            'user_id' => $lesser->id,
            'initiative_id' => $initiative->id,
        ]);

        $component = Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Onze topbijdragers')
            ->assertSee('Top Bijdrager')
            ->assertSee('Minder Bijdrager');

        $html = $component->html();
        $topPos = strpos($html, 'Top Bijdrager');
        $lesserPos = strpos($html, 'Minder Bijdrager');
        $this->assertNotFalse($topPos);
        $this->assertNotFalse($lesserPos);
        $this->assertLessThan($lesserPos, $topPos, 'Top contributor should appear before lesser contributor');
    }

    public function test_community_engagers_shows_active_commenters(): void
    {
        $initiative = Initiative::factory()->published()->create();

        // Create 3 engagers (minimum to show section)
        for ($i = 1; $i <= 3; $i++) {
            $engager = User::factory()->create(['first_name' => "Engager{$i}", 'last_name' => 'Test']);
            $fiche = Fiche::factory()->published()->create([
                'user_id' => $engager->id,
                'initiative_id' => $initiative->id,
            ]);

            // Give each engager some kudos and comments
            Like::create([
                'user_id' => $engager->id,
                'likeable_type' => Fiche::class,
                'likeable_id' => $fiche->id,
                'type' => 'kudos',
                'count' => 1,
            ]);

            Comment::create([
                'user_id' => $engager->id,
                'commentable_type' => Fiche::class,
                'commentable_id' => $fiche->id,
                'body' => 'Geweldig!',
            ]);
        }

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSee('Betrokken collega\'s', escape: false)
            ->assertSee('Engager1 Test')
            ->assertSee('Engager2 Test')
            ->assertSee('Engager3 Test');
    }

    public function test_community_engagers_hidden_when_fewer_than_three(): void
    {
        $initiative = Initiative::factory()->published()->create();

        // Only 2 engagers
        for ($i = 1; $i <= 2; $i++) {
            $engager = User::factory()->create();
            $fiche = Fiche::factory()->published()->create([
                'user_id' => $engager->id,
                'initiative_id' => $initiative->id,
            ]);

            Comment::create([
                'user_id' => $engager->id,
                'commentable_type' => Fiche::class,
                'commentable_id' => $fiche->id,
                'body' => 'Test',
            ]);
        }

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertDontSee('Betrokken collega\'s', escape: false);
    }

    public function test_curated_sections_hidden_when_searching(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create(['first_name' => 'Zoekbaar', 'last_name' => 'Persoon']);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->set('search', 'Zoekbaar')
            ->assertDontSee('Wie deelde er recent?')
            ->assertDontSee('Welkom, nieuwe bijdragers')
            ->assertDontSee('Onze topbijdragers')
            ->assertDontSee('Betrokken collega\'s', escape: false);
    }

    public function test_full_directory_sorted_by_latest_activity(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $olderUser = User::factory()->create(['first_name' => 'Ouder', 'last_name' => 'Persoon']);
        Fiche::factory()->published()->create([
            'user_id' => $olderUser->id,
            'initiative_id' => $initiative->id,
            'created_at' => now()->subMonths(3),
        ]);

        $newerUser = User::factory()->create(['first_name' => 'Nieuwer', 'last_name' => 'Persoon']);
        Fiche::factory()->published()->create([
            'user_id' => $newerUser->id,
            'initiative_id' => $initiative->id,
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(\App\Livewire\ContributorIndex::class)
            ->assertSeeInOrder(['Nieuwer Persoon', 'Ouder Persoon']);
    }

    public function test_show_page_displays_specializations(): void
    {
        $initiative1 = Initiative::factory()->published()->create(['title' => 'Muziektherapie']);
        $initiative2 = Initiative::factory()->published()->create(['title' => 'Creatief atelier']);
        $initiative3 = Initiative::factory()->published()->create(['title' => 'Natuurwandeling']);
        $user = User::factory()->create();

        Fiche::factory()->published()->count(3)->create(['user_id' => $user->id, 'initiative_id' => $initiative1->id]);
        Fiche::factory()->published()->count(2)->create(['user_id' => $user->id, 'initiative_id' => $initiative2->id]);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative3->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        // Specialization pills are integrated into the hero
        $response->assertSee('Muziektherapie');
        $response->assertSee('Creatief atelier');
        $response->assertSee('Natuurwandeling');
    }

    public function test_show_page_displays_kudos_per_fiche(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        // Need 2 fiches so initiative section is not compact
        Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'kudos_count' => 7,
        ]);
        Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'kudos_count' => 0,
        ]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        // Kudos shown per-fiche
        $response->assertSeeHtml('>7</span>');
    }

    public function test_show_page_displays_member_since(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create(['created_at' => now()->subYears(2)]);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('Lid sinds '.$user->created_at->format('Y'));
    }

    public function test_show_page_highlights_featured_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'featured_month' => '2025-06',
        ]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('Fiche van de maand');
    }

    public function test_show_page_displays_fiche_list_items(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('fiche-list-item', escape: false);
        $response->assertSee('fiche-list-icon', escape: false);
    }

    public function test_show_page_shows_related_contributors(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $user = User::factory()->create(['first_name' => 'Hoofd', 'last_name' => 'Bijdrager']);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $relatedUser = User::factory()->create(['first_name' => 'Collega', 'last_name' => 'Bijdrager']);
        Fiche::factory()->published()->create(['user_id' => $relatedUser->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('Collega-bijdragers');
        $response->assertSee('Collega Bijdrager');
    }

    public function test_show_page_hides_related_when_none(): void
    {
        $initiative1 = Initiative::factory()->published()->create();
        $initiative2 = Initiative::factory()->published()->create();

        $user = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative1->id]);

        // Other user in a different initiative — not related
        $other = User::factory()->create();
        Fiche::factory()->published()->create(['user_id' => $other->id, 'initiative_id' => $initiative2->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertDontSee('Collega-bijdragers');
    }

    public function test_show_page_empty_bio_hides_bio_section(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create(['bio' => null]);
        Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        // No bio placeholder shown to visitors — bio section simply hidden
        $response->assertDontSee('Nog geen bio');
    }
}
