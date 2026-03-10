<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
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
            ->assertSeeHtml('>3<');
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
        $response->assertSee('Deel jouw ervaring');
        $response->assertSee('Registreer');
    }

    public function test_authenticated_user_does_not_see_cta(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('contributors.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Deel jouw ervaring');
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
}
