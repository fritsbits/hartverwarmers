<?php

namespace Tests\Feature\Jobs;

use App\Ai\Agents\IconSelector;
use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignFicheIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_icon_from_ai_response(): void
    {
        IconSelector::fake(fn (string $prompt) => 'music');

        $fiche = Fiche::factory()->create(['title' => 'Eurosong quiz']);

        // Reset icon since observer may have set it
        $fiche->updateQuietly(['icon' => null]);

        (new AssignFicheIcon($fiche))->handle();

        $this->assertEquals('music', $fiche->fresh()->icon);
    }

    public function test_falls_back_to_file_text_for_invalid_icon(): void
    {
        IconSelector::fake(fn (string $prompt) => 'nonexistent-icon');

        $fiche = Fiche::factory()->create(['title' => 'Test activiteit']);
        $fiche->updateQuietly(['icon' => null]);

        (new AssignFicheIcon($fiche))->handle();

        $this->assertEquals('file-text', $fiche->fresh()->icon);
    }

    public function test_includes_description_in_prompt(): void
    {
        IconSelector::fake(fn (string $prompt) => 'flower-2');

        $fiche = Fiche::factory()->create([
            'title' => 'Bloemen schikken',
            'description' => '<p>Een gezellige activiteit met verse bloemen.</p>',
        ]);
        $fiche->updateQuietly(['icon' => null]);

        (new AssignFicheIcon($fiche))->handle();

        IconSelector::assertPrompted(function ($prompt) {
            return $prompt->contains('Bloemen schikken')
                && $prompt->contains('Een gezellige activiteit');
        });
    }
}
