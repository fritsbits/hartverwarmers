<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheAiSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    private function createFiche(array $overrides = []): Fiche
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        return Fiche::factory()
            ->for($initiative)
            ->for($user)
            ->create($overrides);
    }

    public function test_has_unused_suggestions_returns_false_when_null(): void
    {
        $fiche = $this->createFiche(['ai_suggestions' => null]);

        $this->assertFalse($fiche->hasUnusedSuggestions());
    }

    public function test_has_unused_suggestions_returns_true_with_unapplied_fields(): void
    {
        $fiche = $this->createFiche([
            'ai_suggestions' => [
                'title' => 'Een betere titel',
                'description' => 'Een betere beschrijving',
                'applied' => [],
            ],
        ]);

        $this->assertTrue($fiche->hasUnusedSuggestions());
    }

    public function test_has_unused_suggestions_returns_false_when_all_applied(): void
    {
        $fiche = $this->createFiche([
            'ai_suggestions' => [
                'title' => 'Een betere titel',
                'description' => 'Een betere beschrijving',
                'applied' => ['title', 'description'],
            ],
        ]);

        $this->assertFalse($fiche->hasUnusedSuggestions());
    }

    public function test_has_unused_suggestions_returns_false_when_all_fields_empty(): void
    {
        $fiche = $this->createFiche([
            'ai_suggestions' => [
                'title' => '',
                'description' => '',
                'preparation' => '',
                'inventory' => '',
                'process' => '',
                'applied' => [],
            ],
        ]);

        $this->assertFalse($fiche->hasUnusedSuggestions());
    }

    public function test_has_unused_suggestions_returns_false_when_all_fields_null(): void
    {
        $fiche = $this->createFiche([
            'ai_suggestions' => [
                'title' => null,
                'description' => null,
                'preparation' => null,
                'inventory' => null,
                'process' => null,
                'applied' => [],
            ],
        ]);

        $this->assertFalse($fiche->hasUnusedSuggestions());
    }

    public function test_should_show_suggestion_nudge_requires_low_score_and_unused(): void
    {
        $fiche = $this->createFiche([
            'presentation_score' => 50,
            'ai_suggestions' => [
                'title' => 'Een betere titel',
                'applied' => [],
            ],
        ]);

        $this->assertTrue($fiche->shouldShowSuggestionNudge());
    }

    public function test_should_show_suggestion_nudge_false_when_score_above_threshold(): void
    {
        $fiche = $this->createFiche([
            'presentation_score' => 60,
            'ai_suggestions' => [
                'title' => 'Een betere titel',
                'applied' => [],
            ],
        ]);

        $this->assertFalse($fiche->shouldShowSuggestionNudge());
    }

    public function test_should_show_suggestion_nudge_false_when_score_is_null(): void
    {
        $fiche = $this->createFiche([
            'presentation_score' => null,
            'ai_suggestions' => [
                'title' => 'Een betere titel',
                'applied' => [],
            ],
        ]);

        $this->assertFalse($fiche->shouldShowSuggestionNudge());
    }
}
