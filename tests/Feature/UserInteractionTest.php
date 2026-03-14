<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\User;
use App\Models\UserInteraction;
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
}
