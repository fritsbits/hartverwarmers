<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\User;
use App\Models\UserInteraction;
use App\Services\FicheInteractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->expectException(\Illuminate\Database\QueryException::class);

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
}
