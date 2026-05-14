<?php

namespace Tests\Feature\Interactions;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\User;
use App\Models\UserInteraction;
use App\Services\FicheInteractionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_view_interaction(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        $interaction = UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $this->assertDatabaseHas('user_interactions', [
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);
        $this->assertNotNull($interaction->created_at);
    }

    public function test_unique_constraint_prevents_duplicate_interactions(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $this->expectException(QueryException::class);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);
    }

    public function test_same_user_can_have_view_and_download_for_same_fiche(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);

        $this->assertDatabaseCount('user_interactions', 2);
    }

    public function test_interactions_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $user->forceDelete();

        $this->assertDatabaseCount('user_interactions', 0);
    }

    public function test_service_returns_interaction_types_for_user(): void
    {
        $user = User::factory()->create();
        $fiche1 = Fiche::factory()->published()->create();
        $fiche2 = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche1->id,
            'type' => 'view',
        ]);
        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche1->id,
            'type' => 'download',
        ]);
        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche2->id,
            'type' => 'view',
        ]);

        $service = new FicheInteractionService;
        $result = $service->forUser($user, [$fiche1->id, $fiche2->id]);

        $this->assertCount(2, $result);
        $this->assertContains('view', $result[$fiche1->id]);
        $this->assertContains('download', $result[$fiche1->id]);
        $this->assertContains('view', $result[$fiche2->id]);
        $this->assertNotContains('download', $result[$fiche2->id]);
    }

    public function test_service_returns_empty_array_for_guest(): void
    {
        $service = new FicheInteractionService;
        $result = $service->forUser(null, [1, 2, 3]);

        $this->assertEmpty($result);
    }

    public function test_service_returns_empty_array_for_empty_fiche_ids(): void
    {
        $user = User::factory()->create();
        $service = new FicheInteractionService;
        $result = $service->forUser($user, []);

        $this->assertEmpty($result);
    }

    public function test_viewing_fiche_page_creates_view_interaction(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));

        $this->assertDatabaseHas('user_interactions', [
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);
    }

    public function test_viewing_fiche_page_does_not_duplicate_view_interaction(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));
        $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));

        $this->assertDatabaseCount('user_interactions', 1);
    }

    public function test_guest_viewing_fiche_page_does_not_create_interaction(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $this->get(route('fiches.show', [$initiative, $fiche]));

        $this->assertDatabaseCount('user_interactions', 0);
    }

    public function test_downloading_fiche_creates_download_interaction(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('files/test-file.pdf', 'test content');

        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        File::factory()->create(['fiche_id' => $fiche->id, 'path' => 'files/test-file.pdf']);

        $this->actingAs($user)->get(route('fiches.download', [$initiative, $fiche]));

        $this->assertDatabaseHas('user_interactions', [
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);
    }

    public function test_downloading_fiche_also_increments_global_download_count(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('files/test-file.pdf', 'test content');

        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'download_count' => 0]);
        File::factory()->create(['fiche_id' => $fiche->id, 'path' => 'files/test-file.pdf']);

        $this->actingAs($user)->get(route('fiches.download', [$initiative, $fiche]));

        $this->assertEquals(1, $fiche->fresh()->download_count);
    }

    public function test_initiative_show_passes_interaction_data_for_logged_in_user(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $response = $this->actingAs($user)->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertViewHas('ficheInteractions');
        $interactions = $response->viewData('ficheInteractions');
        $this->assertArrayHasKey($fiche->id, $interactions);
        $this->assertContains('view', $interactions[$fiche->id]);
    }

    public function test_initiative_show_passes_empty_interactions_for_guest(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertViewHas('ficheInteractions', []);
    }

    public function test_fiche_show_passes_interaction_data_for_logged_in_user(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $otherFiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $otherFiche->id,
            'type' => 'view',
        ]);

        $response = $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertViewHas('ficheInteractions');
        $interactions = $response->viewData('ficheInteractions');
        $this->assertArrayHasKey($otherFiche->id, $interactions);
    }

    public function test_guest_downloading_fiche_is_redirected_to_login_and_creates_no_interaction(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('files/test-file.pdf', 'test content');

        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        File::factory()->create(['fiche_id' => $fiche->id, 'path' => 'files/test-file.pdf']);

        $response = $this->get(route('fiches.download', [$initiative, $fiche]));
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('user_interactions', [
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);
    }
}
