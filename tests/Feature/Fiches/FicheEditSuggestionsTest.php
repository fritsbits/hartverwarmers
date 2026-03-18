<?php

namespace Tests\Feature\Fiches;

use App\Livewire\FicheEdit;
use App\Models\Fiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FicheEditSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_shows_suggestion_panel_when_suggestions_exist(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->withSuggestions()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('fiches.edit', $fiche))
            ->assertStatus(200)
            ->assertSee('Suggestie');
    }

    public function test_edit_page_renders_without_suggestions(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'ai_suggestions' => null,
        ]);

        $this->actingAs($user)
            ->get(route('fiches.edit', $fiche))
            ->assertStatus(200)
            ->assertDontSee('Suggestie');
    }

    public function test_apply_suggestion_updates_field_content(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->withSuggestions()->create([
            'user_id' => $user->id,
            'description' => '',
        ]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->call('applySuggestion', 'description')
            ->assertSet('description', '<p>AI-suggestie voor de beschrijving.</p>');
    }

    public function test_apply_title_suggestion_replaces_value(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->withSuggestions()->create([
            'user_id' => $user->id,
            'title' => 'Originele titel',
        ]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->call('applySuggestion', 'title')
            ->assertSet('title', 'Verbeterde titel voor deze activiteit');
    }

    public function test_apply_content_suggestion_appends_to_existing(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->withSuggestions()->create([
            'user_id' => $user->id,
            'description' => '<p>Bestaande tekst.</p>',
        ]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->call('applySuggestion', 'description')
            ->assertSet('description', "<p>Bestaande tekst.</p>\n<p>AI-suggestie voor de beschrijving.</p>");
    }

    public function test_save_persists_updated_applied_array(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->withSuggestions()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->call('applySuggestion', 'description')
            ->call('save');

        $fiche->refresh();
        $this->assertContains('description', $fiche->ai_suggestions['applied']);
    }
}
