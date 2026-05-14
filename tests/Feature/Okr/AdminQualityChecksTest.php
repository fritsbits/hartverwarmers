<?php

namespace Tests\Feature\Okr;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\User;
use App\Models\UserInteraction;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQualityChecksTest extends TestCase
{
    use RefreshDatabase;

    public function test_presentatiekwaliteit_tab_renders_quality_check_cards(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'presentatiekwaliteit']));

        $response->assertOk();
        $response->assertSee('Laagste 5 scores');
        $response->assertSee('Recente AI-acceptances');
    }

    public function test_bedankjes_tab_renders_quality_check_cards(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'bedankjes']));

        $response->assertOk();
        $response->assertSee('Recente bedank-reacties');
        $response->assertSee('Vaakst bedankt');
    }

    public function test_bedankjes_tab_surfaces_post_download_comment_body(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $reader = User::factory()->create();
        $author = User::factory()->create();
        $fiche = Fiche::factory()->for($author, 'user')->create(['published' => true]);

        UserInteraction::create([
            'user_id' => $reader->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(2),
        ]);

        Comment::factory()->create([
            'user_id' => $reader->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Heb dit met bewoner Maria gedaan, raakte haar erg.',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'bedankjes']));

        $response->assertOk();
        $response->assertSee('Heb dit met bewoner Maria gedaan, raakte haar erg.');
    }

    public function test_onboarding_tab_renders_funnel_table_and_stalled_card(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        // Create a recent verified user so the funnel-table has at least one row.
        User::factory()->create([
            'created_at' => now()->subDays(2),
            'email_verified_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'onboarding']));

        $response->assertOk();
        $response->assertSee('Laatste 5 geverifieerde gebruikers');
        $response->assertSee('Wacht op verificatie');
        $response->assertSee('Return 7d');
    }

    public function test_nieuwsbrief_tab_renders_unsubscribes_card(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'nieuwsbrief']));

        $response->assertOk();
        $response->assertSee('Recente uitschrijvingen');
    }
}
