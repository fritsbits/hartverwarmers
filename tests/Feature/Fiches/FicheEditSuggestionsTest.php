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
            ->assertSet('description', 'AI-suggestie voor de beschrijving.');
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

    public function test_apply_content_suggestion_replaces_existing_description(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->withSuggestions()->create([
            'user_id' => $user->id,
            'description' => '<p>Bestaande tekst.</p>',
        ]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->call('applySuggestion', 'description')
            ->assertSet('description', 'AI-suggestie voor de beschrijving.');
    }

    public function test_rich_text_suggestions_replace_existing_content_and_are_idempotent(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->withSuggestions()->create(['user_id' => $user->id]);
        $component = Livewire::actingAs($user)->test(FicheEdit::class, ['fiche' => $fiche]);

        foreach (['preparation', 'inventory', 'process'] as $field) {
            $component->set($field, '<p>Mijn tekst</p>')
                ->set('aiSuggestions.'.$field, '<ul><li>Suggestie</li></ul>')
                ->call('applySuggestion', $field)
                ->call('applySuggestion', $field)
                ->assertSet($field, '<ul><li>Suggestie</li></ul>');
        }
    }

    public function test_description_takeover_decodes_html_and_preserves_line_breaks(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->withSuggestions()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)->test(FicheEdit::class, ['fiche' => $fiche])
            ->set('aiSuggestions.description', '<p>Samen &amp; gezellig.</p><p>Tweede zin.<br>Derde zin.</p>')
            ->call('applySuggestion', 'description')
            ->assertSet('description', "Samen & gezellig.\nTweede zin.\nDerde zin.");
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
