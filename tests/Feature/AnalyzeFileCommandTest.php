<?php

namespace Tests\Feature;

use App\Ai\Agents\AnalyzeFileContentAgent;
use App\Ai\Agents\MatchInitiativeAgent;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyzeFileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fails_without_file_option(): void
    {
        $this->mockAiAvailable();

        $this->artisan('ai:analyze-file')
            ->assertFailed();
    }

    public function test_command_fails_for_nonexistent_file(): void
    {
        $this->mockAiAvailable();

        $this->artisan('ai:analyze-file', ['--file' => 999])
            ->assertFailed();
    }

    public function test_command_fails_for_file_without_extracted_text(): void
    {
        $this->mockAiAvailable();

        $file = File::factory()->create(['extracted_text' => null]);

        $this->artisan('ai:analyze-file', ['--file' => $file->id])
            ->assertFailed();
    }

    public function test_command_fails_when_ai_unavailable(): void
    {
        config(['ai.providers.anthropic.key' => null]);

        $this->artisan('ai:analyze-file', ['--file' => 1])
            ->assertFailed();
    }

    public function test_command_runs_successfully_with_valid_file(): void
    {
        AnalyzeFileContentAgent::fake();
        MatchInitiativeAgent::fake();

        $file = File::factory()->create(['extracted_text' => 'Test content for analysis']);
        Initiative::factory()->published()->create();

        $this->artisan('ai:analyze-file', ['--file' => $file->id])
            ->assertSuccessful();

        AnalyzeFileContentAgent::assertPrompted(fn ($prompt) => $prompt->contains('Test content'));
        MatchInitiativeAgent::assertPrompted(fn ($prompt) => $prompt->contains($file->original_filename));
    }

    public function test_command_accepts_title_and_description_options(): void
    {
        AnalyzeFileContentAgent::fake();
        MatchInitiativeAgent::fake();

        $file = File::factory()->create(['extracted_text' => 'Test content']);
        Initiative::factory()->published()->create();

        $this->artisan('ai:analyze-file', [
            '--file' => $file->id,
            '--title' => 'Custom Title',
            '--description' => 'Custom Description',
        ])->assertSuccessful();

        AnalyzeFileContentAgent::assertPrompted(function ($prompt) {
            return $prompt->contains('Custom Title')
                && $prompt->contains('Custom Description');
        });
    }

    public function test_command_outputs_table_headers(): void
    {
        AnalyzeFileContentAgent::fake();
        MatchInitiativeAgent::fake();

        $file = File::factory()->create(['extracted_text' => 'Test content']);
        Initiative::factory()->published()->create();

        $this->artisan('ai:analyze-file', ['--file' => $file->id])
            ->expectsOutputToContain('Agent')
            ->expectsOutputToContain('TOTAL')
            ->assertSuccessful();
    }

    private function mockAiAvailable(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
    }
}
