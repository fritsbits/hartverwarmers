<?php

namespace Tests\Feature\Fiches;

use App\Ai\Agents\FicheQualityAgent;
use App\Jobs\AssessFicheQuality;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FicheQualityScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure AI is "available" so the observer dispatches quality jobs
        config(['ai.providers.anthropic.key' => 'test-key']);
    }

    private function createPublishedFiche(array $overrides = []): Fiche
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        return Fiche::factory()
            ->for($initiative)
            ->for($user)
            ->published()
            ->create($overrides);
    }

    public function test_quality_job_is_dispatched_when_fiche_becomes_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $fiche = Fiche::factory()
            ->for($initiative)
            ->for($user)
            ->create(['published' => false]);

        Queue::assertNotPushed(AssessFicheQuality::class);

        $fiche->update(['published' => true]);

        Queue::assertPushed(AssessFicheQuality::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function test_quality_job_is_not_dispatched_for_draft_fiche(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()
            ->for($initiative)
            ->for($user)
            ->create(['published' => false]);

        Queue::assertNotPushed(AssessFicheQuality::class);
    }

    public function test_quality_job_is_dispatched_when_content_fields_change(): void
    {
        Queue::fake();

        $fiche = $this->createPublishedFiche();

        // Clear the queue from the create dispatch
        Queue::assertPushed(AssessFicheQuality::class);
        Queue::fake();

        $fiche->update(['description' => 'Updated description content']);

        Queue::assertPushed(AssessFicheQuality::class);
    }

    public function test_quality_job_is_not_dispatched_for_non_content_field_changes(): void
    {
        Queue::fake();

        $fiche = $this->createPublishedFiche();
        Queue::fake(); // Reset after create

        $fiche->update(['download_count' => 5]);

        Queue::assertNotPushed(AssessFicheQuality::class);
    }

    public function test_quality_job_is_not_dispatched_within_debounce_window(): void
    {
        Queue::fake();

        $fiche = $this->createPublishedFiche([
            'quality_assessed_at' => now()->subMinutes(5),
        ]);
        Queue::fake(); // Reset after create

        $fiche->update(['description' => 'Changed description']);

        Queue::assertNotPushed(AssessFicheQuality::class);
    }

    public function test_quality_job_is_dispatched_after_debounce_window(): void
    {
        Queue::fake();

        $fiche = $this->createPublishedFiche([
            'quality_assessed_at' => now()->subMinutes(11),
        ]);
        Queue::fake(); // Reset after create

        $fiche->update(['description' => 'Changed description']);

        Queue::assertPushed(AssessFicheQuality::class);
    }

    public function test_assess_fiche_quality_job_writes_score_to_fiche(): void
    {
        $fiche = $this->createPublishedFiche([
            'description' => str_repeat('Mooie activiteit ', 20),
        ]);

        // Use laravel/ai's built-in fake to avoid real API calls.
        // Responses must be a list of response arrays — wrap in an outer array.
        FicheQualityAgent::fake([
            ['score' => 75, 'justification' => 'Goede aansluiting bij DIAMANT.'],
        ]);

        $job = new AssessFicheQuality($fiche);
        $job->handle();

        $fiche->refresh();
        $this->assertEquals(75, $fiche->quality_score);
        $this->assertEquals('Goede aansluiting bij DIAMANT.', $fiche->quality_justification);
        $this->assertNotNull($fiche->quality_assessed_at);
    }
}
