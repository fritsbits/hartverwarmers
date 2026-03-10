<?php

namespace Tests\Feature;

use App\Livewire\FicheKudos;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KudosTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_give_kudos(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 1);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
        ]);
    }

    public function test_kudos_count_increments_on_multiple_clicks(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 5);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 5,
        ]);
    }

    public function test_kudos_capped_at_25(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 10);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);
    }

    public function test_guest_can_give_kudos_via_session(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 3);

        $this->assertDatabaseHas('likes', [
            'user_id' => null,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 3,
        ]);
    }

    public function test_guest_kudos_capped_at_10(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $component = Livewire::test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 8)
            ->call('addKudos', amount: 8);

        $this->assertDatabaseHas('likes', [
            'user_id' => null,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 10,
        ]);
    }

    public function test_guest_kudos_persist_in_same_session(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 5);

        $fresh = Livewire::test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(5, $fresh->get('myKudos'));
        $this->assertEquals(5, $fresh->get('totalKudos'));
    }

    public function test_bookmark_toggle_creates_bookmark(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('toggleBookmark');

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);
    }

    public function test_bookmark_toggle_removes_existing_bookmark(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('toggleBookmark');

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);
    }

    public function test_guest_sees_inline_auth_when_toggling_bookmark(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheKudos::class, ['fiche' => $fiche])
            ->call('toggleBookmark')
            ->assertSet('showBookmarkAuth', true)
            ->assertNoRedirect();
    }

    public function test_guest_can_bookmark_via_inline_auth(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheKudos::class, ['fiche' => $fiche])
            ->call('toggleBookmark')
            ->set('guestName', 'Sophie Van Damme')
            ->set('guestEmail', 'sophie@example.com')
            ->set('guestTerms', true)
            ->call('guestBookmark');

        $user = User::where('email', 'sophie@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);
    }

    public function test_total_kudos_shows_sum_across_users(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::factory()->kudos(10)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);
        Like::factory()->kudos(5)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        $component = Livewire::test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(15, $component->get('totalKudos'));
    }

    public function test_kudos_persist_on_fresh_component_mount(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // Give kudos via the component
        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 8);

        // Mount a fresh component (simulates page reload)
        $fresh = Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(8, $fresh->get('totalKudos'));
        $this->assertEquals(8, $fresh->get('myKudos'));
    }

    public function test_multiple_batched_add_kudos_calls_accumulate(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // Simulate multiple batched calls (like rapid hold-and-release cycles)
        $component = Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche]);

        $component->call('addKudos', amount: 5);
        $component->call('addKudos', amount: 3);
        $component->call('addKudos', amount: 7);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 15,
        ]);

        // Verify fresh mount shows accumulated total
        $fresh = Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(15, $fresh->get('totalKudos'));
    }

    public function test_kudos_partially_capped_when_near_limit(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // Pre-seed 20 kudos
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 20,
        ]);

        // Try to add 10 more — only 5 should be added
        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 10);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);
    }

    public function test_amount_clamped_to_minimum_of_1(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 0);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
        ]);
    }

    public function test_amount_clamped_to_maximum_of_25(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 100);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);
    }

    public function test_multi_user_kudos_totals_on_fresh_mount(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // Each user gives kudos
        Livewire::actingAs($user1)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 10);

        Livewire::actingAs($user2)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 20);

        Livewire::actingAs($user3)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 5);

        // Fresh mount as user1 — totalKudos should be 35, myKudos should be 10
        $fresh = Livewire::actingAs($user1)
            ->test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(35, $fresh->get('totalKudos'));
        $this->assertEquals(10, $fresh->get('myKudos'));
        $this->assertEquals(3, $fresh->get('kudosGiversCount'));
    }

    public function test_capped_user_add_kudos_does_not_increment(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);

        // Call addKudos multiple times — count should stay at 25
        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 5)
            ->call('addKudos', amount: 10)
            ->call('addKudos', amount: 1);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);
    }

    public function test_my_kudos_reflects_current_user_count(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 25,
        ]);
        Like::create([
            'user_id' => $otherUser->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 10,
        ]);

        $component = Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(25, $component->get('myKudos'));
        $this->assertEquals(35, $component->get('totalKudos'));
    }

    public function test_kudos_givers_count_only_includes_nonzero(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // Create a kudos with count 0 (should not be counted)
        Like::factory()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 0,
        ]);

        // Create a kudos with count > 0
        Like::factory()->kudos(5)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        $component = Livewire::test(FicheKudos::class, ['fiche' => $fiche]);

        $this->assertEquals(1, $component->get('kudosGiversCount'));
    }

    public function test_author_cannot_give_kudos_to_own_fiche(): void
    {
        $author = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $author->id,
        ]);

        Livewire::actingAs($author)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 5);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $author->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
        ]);
    }

    public function test_other_user_can_give_kudos_to_authored_fiche(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $author->id,
        ]);

        Livewire::actingAs($otherUser)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->call('addKudos', amount: 5);

        $this->assertDatabaseHas('likes', [
            'user_id' => $otherUser->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 5,
        ]);
    }

    public function test_is_own_fiche_computed_property(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $author->id,
        ]);

        // Author sees isOwnFiche as true
        $component = Livewire::actingAs($author)
            ->test(FicheKudos::class, ['fiche' => $fiche]);
        $this->assertTrue($component->get('isOwnFiche'));

        // Other user sees isOwnFiche as false
        $component = Livewire::actingAs($otherUser)
            ->test(FicheKudos::class, ['fiche' => $fiche]);
        $this->assertFalse($component->get('isOwnFiche'));
    }
}
