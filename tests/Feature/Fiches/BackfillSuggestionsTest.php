<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\File;
use App\Services\FicheAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_processes_fiches_with_extracted_text(): void
    {
        $fiche = Fiche::factory()->published()->create(['ai_suggestions' => null]);
        // Create a file with extracted_text for this fiche
        File::factory()->create([
            'fiche_id' => $fiche->id,
            'extracted_text' => 'Some extracted text about an activity.',
        ]);

        $this->mock(FicheAiService::class, function ($mock) {
            $mock->shouldReceive('analyzeFiles')
                ->once()
                ->andReturn([
                    'suggested_title' => 'Better title',
                    'description' => '**AI desc**',
                    'preparation' => '**AI prep**',
                    'inventory' => '',
                    'process' => '',
                    'duration_estimate' => '30 min',
                    'group_size_estimate' => '4-8',
                    'suggested_themes' => [],
                    'suggested_goals' => [],
                    'suggested_target_audience' => [],
                    '_meta' => [],
                ]);
        });

        $this->artisan('fiches:backfill-suggestions')
            ->assertExitCode(0);

        $fiche->refresh();
        $this->assertNotNull($fiche->ai_suggestions);
        $this->assertEquals('Better title', $fiche->ai_suggestions['title']);
        $this->assertStringContainsString('<strong>', $fiche->ai_suggestions['description']);
        $this->assertEquals([], $fiche->ai_suggestions['applied']);
    }

    public function test_backfill_skips_fiches_without_extracted_text(): void
    {
        Fiche::factory()->published()->create(['ai_suggestions' => null]);

        $this->artisan('fiches:backfill-suggestions')
            ->assertExitCode(0);

        $this->assertNull(Fiche::first()->ai_suggestions);
    }

    public function test_backfill_skips_fiches_that_already_have_suggestions(): void
    {
        $fiche = Fiche::factory()->published()->withSuggestions()->create();
        File::factory()->create([
            'fiche_id' => $fiche->id,
            'extracted_text' => 'Text',
        ]);

        $this->mock(FicheAiService::class, function ($mock) {
            $mock->shouldNotReceive('analyzeFiles');
        });

        $this->artisan('fiches:backfill-suggestions')
            ->assertExitCode(0);
    }

    public function test_backfill_respects_limit_option(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $fiche = Fiche::factory()->published()->create(['ai_suggestions' => null]);
            File::factory()->create([
                'fiche_id' => $fiche->id,
                'extracted_text' => 'Text',
            ]);
        }

        $callCount = 0;
        $this->mock(FicheAiService::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('analyzeFiles')
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;

                    return [
                        'suggested_title' => 'Title',
                        'description' => 'Desc',
                        'preparation' => '',
                        'inventory' => '',
                        'process' => '',
                        'duration_estimate' => '',
                        'group_size_estimate' => '',
                        'suggested_themes' => [],
                        'suggested_goals' => [],
                        'suggested_target_audience' => [],
                        '_meta' => [],
                    ];
                });
        });

        $this->artisan('fiches:backfill-suggestions --limit=2')
            ->assertExitCode(0);

        $this->assertEquals(2, $callCount);
    }
}
