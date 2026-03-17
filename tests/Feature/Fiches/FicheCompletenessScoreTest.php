<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheCompletenessScoreTest extends TestCase
{
    use RefreshDatabase;

    private function createFiche(array $overrides = []): Fiche
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        return Fiche::factory()
            ->for($initiative)
            ->for($user)
            ->published()
            ->create($overrides);
    }

    public function test_completeness_score_is_zero_when_all_fields_empty(): void
    {
        $fiche = $this->createFiche([
            'description' => '',
            'materials' => ['preparation' => '', 'inventory' => '', 'process' => ''],
        ]);

        $this->assertEquals(0, $fiche->fresh()->completeness_score);
    }

    public function test_completeness_score_is100_when_all_fields_filled(): void
    {
        $fiche = $this->createFiche([
            'description' => str_repeat('a', 100),
            'materials' => [
                'preparation' => 'Zet alles klaar',
                'inventory' => 'Papier, schaar, lijm',
                'process' => 'Stap 1: begin met knippen',
            ],
        ]);

        $this->assertEquals(100, $fiche->fresh()->completeness_score);
    }

    public function test_completeness_score_is50_when_two_fields_filled(): void
    {
        $fiche = $this->createFiche([
            'description' => str_repeat('a', 100),
            'materials' => [
                'preparation' => 'Zet alles klaar',
                'inventory' => '',
                'process' => '',
            ],
        ]);

        $this->assertEquals(50, $fiche->fresh()->completeness_score);
    }

    public function test_description_under100_chars_scores_zero(): void
    {
        $fiche = $this->createFiche([
            'description' => str_repeat('a', 99),
            'materials' => ['preparation' => '', 'inventory' => '', 'process' => ''],
        ]);

        $this->assertEquals(0, $fiche->fresh()->completeness_score);
    }

    public function test_html_only_materials_fields_count_as_empty(): void
    {
        $fiche = $this->createFiche([
            'description' => str_repeat('a', 100),
            'materials' => [
                'preparation' => '<div><br><br></div>',
                'inventory' => '<p> </p>',
                'process' => 'Echte inhoud hier',
            ],
        ]);

        // description (25) + process (25) = 50
        $this->assertEquals(50, $fiche->fresh()->completeness_score);
    }

    public function test_completeness_score_recalculates_on_update(): void
    {
        $fiche = $this->createFiche([
            'description' => str_repeat('a', 100),
            'materials' => ['preparation' => '', 'inventory' => '', 'process' => ''],
        ]);

        $this->assertEquals(25, $fiche->fresh()->completeness_score);

        $fiche->update(['materials' => [
            'preparation' => 'Nu ingevuld',
            'inventory' => 'Ook ingevuld',
            'process' => 'En dit ook',
        ]]);

        $this->assertEquals(100, $fiche->fresh()->completeness_score);
    }
}
