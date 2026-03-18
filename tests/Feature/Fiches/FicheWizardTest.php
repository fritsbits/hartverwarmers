<?php

namespace Tests\Feature\Fiches;

use App\Jobs\GenerateFilePreview;
use App\Jobs\ProcessFicheUploads;
use App\Livewire\FicheWizard;
use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Tests\TestCase;

class FicheWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('fiches.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_wizard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('fiches.create'))
            ->assertStatus(200)
            ->assertSee('Nieuwe fiche');
    }

    public function test_step1_allows_continuing_without_files(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('submitStep1')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2);
    }

    public function test_step1_button_shows_verder_zonder_bestand_when_no_files(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->assertSee('Verder zonder bestand');
    }

    public function test_step1_button_shows_volgende_when_files_uploaded(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->assertSee('Volgende')
            ->assertDontSee('Verder zonder bestand');
    }

    public function test_publish_works_without_files(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Activiteit zonder bestanden')
            ->set('description', 'Een activiteit zonder uploads')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('publish');

        $this->assertDatabaseHas('fiches', [
            'title' => 'Activiteit zonder bestanden',
            'user_id' => $user->id,
            'published' => true,
        ]);

        $fiche = Fiche::where('title', 'Activiteit zonder bestanden')->first();
        $this->assertCount(0, $fiche->files);
    }

    public function test_step1_accepts_file_uploads(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $component->assertHasNoErrors();
        $this->assertNotEmpty($component->get('uploadedFiles'));
        $this->assertNotNull($component->get('previewFileId'));
        $this->assertDatabaseHas('files', ['original_filename' => 'test.pdf', 'fiche_id' => null]);

        Queue::assertPushed(ProcessFicheUploads::class);
    }

    public function test_step1_rejects_invalid_file_types_with_dutch_message(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload')])
            ->assertHasErrors(['uploads.*']);

        $this->assertStringContainsString(
            'Dit bestandstype wordt niet ondersteund.',
            implode(' ', $component->errors()->all())
        );
    }

    public function test_step1_rejects_oversized_files_with_dutch_message(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('large.pdf', 52000, 'application/pdf')])
            ->assertHasErrors(['uploads.*']);

        $this->assertStringContainsString(
            'Dit bestand is te groot (max 50 MB).',
            // Full message includes advice to resize the file
            implode(' ', $component->errors()->all())
        );
    }

    public function test_first_file_upload_dispatches_processing_with_all_file_ids(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('main.pdf', 100, 'application/pdf')]);

        $previewFileId = $component->get('previewFileId');
        $this->assertNotNull($previewFileId);
        $component->assertSet('processingStep', 'extracting');

        Queue::assertPushed(ProcessFicheUploads::class, function ($job) use ($previewFileId) {
            return $job->previewFileId === $previewFileId
                && count($job->fileIds) === 1
                && $job->fileIds[0] === $previewFileId;
        });
    }

    public function test_step1_advances_without_dispatching(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $file = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->set('previewFileId', $file->id)
            ->set('processingStep', 'extracting')
            ->set('disclaimerAccepted', true)
            ->call('submitStep1');

        $component->assertSet('currentStep', 2);

        Queue::assertNothingPushed();
    }

    public function test_step2_validates_title(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', '')
            ->call('goToStep3')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_step2_allows_empty_description(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', 'Een titel')
            ->set('description', '')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('goToStep3')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 3);
    }

    public function test_step2_advances_to_step3_with_valid_data(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', 'Mijn activiteit')
            ->set('description', 'Een mooie beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('goToStep3')
            ->assertSet('currentStep', 3);
    }

    public function test_check_processing_reads_cache_status(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-key-123')
            ->set('processingStep', 'extracting');

        Cache::put('fiche-processing:test-key-123', [
            'step' => 'analyzing',
            'updated_at' => now()->timestamp,
        ], 3600);

        $component->call('checkProcessing')
            ->assertSet('processingStep', 'analyzing')
            ->assertSet('processingComplete', false);
    }

    public function test_check_processing_completes_on_done(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-key-done')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-key-done', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => [
                'description' => 'Test samenvatting',
                'preparation' => 'AI voorbereiding',
                'inventory' => 'AI benodigdheden',
                'process' => 'AI werkwijze',
                'duration_estimate' => '30 min',
                'group_size_estimate' => '4-8',
                'suggested_goals' => [],
                'suggested_themes' => [],
            ],
            'matched_initiatives' => null,
        ], 3600);

        $component->call('checkProcessing')
            ->assertSet('processingStep', 'done')
            ->assertSet('processingComplete', true);

        $this->assertStringContainsString('AI voorbereiding', $component->get('aiPreparation'));
        $this->assertStringContainsString('Test samenvatting', $component->get('aiDescription'));

        $this->assertNull(Cache::get('fiche-processing:test-key-done'));
    }

    public function test_check_processing_handles_failure(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-key-fail')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-key-fail', [
            'step' => 'failed',
            'updated_at' => now()->timestamp,
            'error' => 'Something went wrong',
        ], 3600);

        $component->call('checkProcessing')
            ->assertSet('processingStep', 'failed')
            ->assertSet('processingComplete', true)
            ->assertSet('processingFailReason', 'error');
    }

    public function test_check_processing_stores_fail_reason_for_no_text(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-key-notext')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-key-notext', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => null,
            'matched_initiatives' => null,
            'reason' => 'no_text_extracted',
        ], 3600);

        $component->call('checkProcessing')
            ->assertSet('processingComplete', true)
            ->assertSet('processingFailReason', 'no_text_extracted');
    }

    public function test_check_processing_detects_no_suggestions(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-key-nosug')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-key-nosug', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => null,
            'matched_initiatives' => null,
        ], 3600);

        $component->call('checkProcessing')
            ->assertSet('processingComplete', true)
            ->assertSet('processingFailReason', 'no_suggestions');
    }

    public function test_fail_reason_shows_message_on_step2(): void
    {
        $user = User::factory()->create();
        Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('processingComplete', true)
            ->set('processingFailReason', 'no_text_extracted')
            ->assertSee('geen tekst uitlezen');
    }

    public function test_fail_reason_shows_message_on_step3(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('processingComplete', true)
            ->set('processingFailReason', 'no_text_extracted')
            ->assertSee('Geen suggesties')
            ->assertSee('geen uitleesbare tekst');
    }

    public function test_fail_reason_cleared_on_new_upload(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingFailReason', 'no_text_extracted')
            ->set('processingComplete', true)
            ->set('processingStep', 'done')
            ->set('uploads', [UploadedFile::fake()->create('new.pdf', 100, 'application/pdf')]);

        $this->assertNull($component->get('processingFailReason'));
    }

    public function test_apply_suggestion_appends_ai_value_to_empty_field(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('aiDescription', '<p>AI beschrijving</p>')
            ->call('applySuggestion', 'description')
            ->assertSet('description', '<p>AI beschrijving</p>')
            ->assertSet('appliedSuggestions', ['description']);
    }

    public function test_apply_suggestion_appends_to_existing_content(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('description', '<p>Mijn tekst</p>')
            ->set('aiDescription', '<p>AI beschrijving</p>')
            ->call('applySuggestion', 'description')
            ->assertSet('description', "<p>Mijn tekst</p>\n<p>AI beschrijving</p>")
            ->assertSet('appliedSuggestions', ['description']);
    }

    public function test_apply_suggestion_does_not_dismiss_card(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('aiDescription', '<p>AI beschrijving</p>')
            ->call('applySuggestion', 'description')
            ->assertSet('dismissedSuggestions', []);
    }

    public function test_dismiss_suggestion_hides_ai_card(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('dismissSuggestion', 'description')
            ->assertSet('dismissedSuggestions', ['description']);
    }

    public function test_apply_suggestion_does_not_duplicate_applied(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('aiPreparation', '<p>AI voorbereiding</p>')
            ->call('applySuggestion', 'preparation')
            ->call('applySuggestion', 'preparation')
            ->assertSet('appliedSuggestions', ['preparation']);
    }

    public function test_apply_suggestion_ignores_null_ai_value(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('description', 'Mijn tekst')
            ->set('aiDescription', null)
            ->call('applySuggestion', 'description')
            ->assertSet('description', 'Mijn tekst')
            ->assertSet('appliedSuggestions', ['description']);
    }

    public function test_publish_saves_field_values_directly(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Mijn titel')
            ->set('description', 'Mijn beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $this->assertDatabaseHas('fiches', [
            'title' => 'Mijn titel',
            'description' => 'Mijn beschrijving',
            'user_id' => $user->id,
            'published' => true,
        ]);
    }

    public function test_publish_after_apply_saves_appended_value(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Mijn titel')
            ->set('description', 'Mijn beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('aiDescription', '<p>AI beschrijving</p>')
            ->call('applySuggestion', 'description')
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $this->assertDatabaseHas('fiches', [
            'title' => 'Mijn titel',
            'description' => "Mijn beschrijving\n<p>AI beschrijving</p>",
            'user_id' => $user->id,
            'published' => true,
        ]);
    }

    public function test_publish_creates_published_fiche(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Mijn activiteit')
            ->set('description', 'Een mooie activiteit voor senioren')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $this->assertDatabaseHas('fiches', [
            'title' => 'Mijn activiteit',
            'user_id' => $user->id,
            'published' => true,
            'initiative_id' => $initiative->id,
        ]);

        $file->refresh();
        $this->assertNotNull($file->fiche_id);
    }

    public function test_save_draft_creates_unpublished_fiche(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Concept fiche')
            ->set('description', 'Een concept')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('saveDraft');

        $this->assertDatabaseHas('fiches', [
            'title' => 'Concept fiche',
            'user_id' => $user->id,
            'published' => false,
        ]);
    }

    public function test_publish_shows_celebration_step_4(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Mijn feestelijke fiche')
            ->set('description', 'Test celebration')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('publish');

        $component->assertSet('currentStep', 4)
            ->assertNotDispatched('redirect');

        $this->assertNotNull($component->get('publishedFicheId'));
        $this->assertNotNull($component->get('publishedFicheUrl'));

        $fiche = Fiche::where('title', 'Mijn feestelijke fiche')->first();
        $this->assertNotNull($fiche);
        $this->assertTrue($fiche->published);
        $this->assertEquals($fiche->id, $component->get('publishedFicheId'));
    }

    public function test_save_draft_redirects_instead_of_step_4(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Concept zonder celebration')
            ->set('description', 'Draft test')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('saveDraft')
            ->assertRedirect();

        $this->assertDatabaseHas('fiches', [
            'title' => 'Concept zonder celebration',
            'published' => false,
        ]);
    }

    public function test_unique_slug_is_generated(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->create(['slug' => 'mijn-activiteit', 'initiative_id' => $initiative->id]);

        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Mijn activiteit')
            ->set('description', 'Beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $newFiche = Fiche::where('title', 'Mijn activiteit')->where('user_id', $user->id)->first();
        $this->assertNotEquals('mijn-activiteit', $newFiche->slug);
        $this->assertStringStartsWith('mijn-activiteit', $newFiche->slug);
    }

    public function test_files_are_attached_to_fiche_on_save(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $file1 = File::factory()->create(['fiche_id' => null]);
        $file2 = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Test fiche')
            ->set('description', 'Beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [
                ['id' => $file1->id, 'name' => $file1->original_filename, 'size' => $file1->size_bytes, 'type' => 'PDF'],
                ['id' => $file2->id, 'name' => $file2->original_filename, 'size' => $file2->size_bytes, 'type' => 'PDF'],
            ])
            ->call('publish');

        $fiche = Fiche::where('title', 'Test fiche')->first();
        $this->assertEquals(2, $fiche->files()->count());
    }

    public function test_tags_are_synced_on_save(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $themeTag = Tag::factory()->theme()->create();
        $goalTag = Tag::factory()->goal()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Tagged fiche')
            ->set('description', 'Beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('selectedThemeTags', [$themeTag->id])
            ->set('selectedGoalTags', [$goalTag->id])
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $fiche = Fiche::where('title', 'Tagged fiche')->first();
        $this->assertTrue($fiche->tags->contains($themeTag));
        $this->assertTrue($fiche->tags->contains($goalTag));
    }

    public function test_save_catches_exception_and_shows_error(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Fiche met fout')
            ->set('description', 'Beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->set('selectedGoalTags', [99999])
            ->call('saveDraft');

        $component->assertHasErrors(['save']);
        $component->assertSet('currentStep', 3);
        $this->assertDatabaseMissing('fiches', ['title' => 'Fiche met fout']);

        $file->refresh();
        $this->assertNull($file->fiche_id);
    }

    public function test_slug_generation_handles_multiple_duplicates(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->create(['slug' => 'test-slug', 'initiative_id' => $initiative->id]);
        Fiche::factory()->create(['slug' => 'test-slug-1', 'initiative_id' => $initiative->id]);

        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Test Slug')
            ->set('description', 'Beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $newFiche = Fiche::where('title', 'Test Slug')->where('user_id', $user->id)->first();
        $this->assertNotNull($newFiche);
        $this->assertEquals('test-slug-2', $newFiche->slug);
    }

    public function test_restore_uploaded_files_filters_deleted(): void
    {
        $user = User::factory()->create();
        $existingFile = File::factory()->create(['fiche_id' => null]);
        $deletedFileId = 99999;

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('restoreUploadedFiles', [$existingFile->id, $deletedFileId]);

        $uploadedFiles = $component->get('uploadedFiles');
        $this->assertCount(1, $uploadedFiles);
        $this->assertEquals($existingFile->id, $uploadedFiles[0]['id']);
    }

    public function test_restore_uploaded_files_excludes_claimed(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        $claimedFile = File::factory()->create(['fiche_id' => $fiche->id]);
        $unclaimedFile = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('restoreUploadedFiles', [$claimedFile->id, $unclaimedFile->id]);

        $uploadedFiles = $component->get('uploadedFiles');
        $this->assertCount(1, $uploadedFiles);
        $this->assertEquals($unclaimedFile->id, $uploadedFiles[0]['id']);
    }

    public function test_suggested_tags_stored_internally_on_step2(): void
    {
        $user = User::factory()->create();

        $themeTag = Tag::factory()->theme()->create();
        $goalTag = Tag::factory()->goal()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', 'Test')
            ->set('suggestedThemeTagIds', [$themeTag->id])
            ->set('suggestedGoalTagIds', [$goalTag->id])
            ->assertSet('suggestedThemeTagIds', [$themeTag->id])
            ->assertSet('suggestedGoalTagIds', [$goalTag->id]);
    }

    public function test_wizard_works_without_ai_processing(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->set('previewFileId', $file->id)
            ->set('processingStep', 'failed')
            ->set('processingComplete', true)
            ->set('disclaimerAccepted', true)
            ->call('submitStep1');

        $component->assertSet('currentStep', 2);

        $component->set('title', 'Handmatige fiche')
            ->set('description', 'Zonder AI')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('goToStep3')
            ->assertSet('currentStep', 3);

        $component->call('publish');

        $this->assertDatabaseHas('fiches', [
            'title' => 'Handmatige fiche',
            'description' => 'Zonder AI',
            'user_id' => $user->id,
            'published' => true,
        ]);
    }

    public function test_ai_markdown_is_converted_to_html(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-md-key')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-md-key', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => [
                'description' => 'Samenvatting',
                'preparation' => "- Stap 1\n- Stap 2\n- Stap 3",
                'inventory' => 'Een **mooie** beschrijving',
                'process' => '',
                'duration_estimate' => '',
                'group_size_estimate' => '',
                'suggested_goals' => [],
                'suggested_themes' => [],
            ],
            'matched_initiatives' => null,
        ], 3600);

        $component->call('checkProcessing');

        $this->assertStringContainsString('<li>Stap 1</li>', $component->get('aiPreparation'));
        $this->assertStringContainsString('<strong>mooie</strong>', $component->get('aiInventory'));
    }

    public function test_ai_html_in_markdown_is_stripped(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-xss-key')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-xss-key', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => [
                'description' => 'Samenvatting',
                'preparation' => 'Test <script>alert("xss")</script>',
                'inventory' => '',
                'process' => '',
                'duration_estimate' => '',
                'group_size_estimate' => '',
                'suggested_goals' => [],
                'suggested_themes' => [],
            ],
            'matched_initiatives' => null,
        ], 3600);

        $component->call('checkProcessing');

        $this->assertStringNotContainsString('<script>', $component->get('aiPreparation'));
    }

    public function test_empty_fields_are_not_auto_filled_from_ai(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Test')
            ->set('description', 'Test')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('preparation', '')
            ->set('aiPreparation', '<p>AI voorbereiding</p>')
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        $fiche = Fiche::where('title', 'Test')->first();
        $this->assertNull($fiche->materials);
    }

    public function test_title_prefills_from_first_uploaded_filename(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('muziekbingo-jaren-60.pdf', 100, 'application/pdf')]);

        $this->assertEquals('Muziekbingo jaren 60', $component->get('title'));
    }

    public function test_title_prefill_does_not_overwrite_existing_title(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Mijn eigen titel')
            ->set('uploads', [UploadedFile::fake()->create('ander-bestand.pdf', 100, 'application/pdf')]);

        $this->assertEquals('Mijn eigen titel', $component->get('title'));
    }

    public function test_stale_processing_detected_when_no_cache_after_30_seconds(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'stale-key')
            ->set('processingStep', 'extracting')
            ->set('processingStartedAt', now()->subSeconds(35)->timestamp);

        $component->call('checkProcessing')
            ->assertSet('processingStale', true);
    }

    public function test_stale_processing_detected_when_cache_updated_at_is_old(): void
    {
        $user = User::factory()->create();

        Cache::put('fiche-processing:stale-cache-key', [
            'step' => 'extracting',
            'updated_at' => now()->subSeconds(35)->timestamp,
        ], 3600);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'stale-cache-key')
            ->set('processingStep', 'extracting')
            ->set('processingStartedAt', now()->subSeconds(40)->timestamp);

        $component->call('checkProcessing')
            ->assertSet('processingStale', true)
            ->assertSet('processingStep', 'extracting');
    }

    public function test_skip_processing_marks_complete(): void
    {
        $user = User::factory()->create();

        Cache::put('fiche-processing:skip-key', [
            'step' => 'extracting',
            'updated_at' => now()->timestamp,
        ], 3600);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'skip-key')
            ->set('processingStep', 'extracting')
            ->call('skipProcessing');

        $component->assertSet('processingComplete', true)
            ->assertSet('processingStep', 'skipped');

        $this->assertNull(Cache::get('fiche-processing:skip-key'));
    }

    public function test_dutch_validation_messages_on_title(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', '')
            ->set('description', 'Een beschrijving')
            ->call('goToStep3')
            ->assertHasErrors(['title']);

        $this->assertStringContainsString(
            'Geef je activiteit een titel.',
            implode(' ', $component->errors()->all())
        );
    }

    public function test_description_is_optional_for_step3(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', 'Een titel')
            ->set('description', '')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('goToStep3')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 3);
    }

    public function test_description_is_required_for_publish(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Een titel')
            ->set('description', '')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('publish')
            ->assertHasErrors(['description' => 'required']);
    }

    public function test_description_is_required_for_draft(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Een titel')
            ->set('description', '')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('saveDraft')
            ->assertHasErrors(['description' => 'required']);
    }

    public function test_publish_validation_error_banner_shows_near_buttons(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Een titel')
            ->set('description', '')
            ->set('selectedInitiativeId', $initiative->id)
            ->call('publish')
            ->assertHasErrors(['description' => 'required'])
            ->assertSee('Geef een beschrijving van je activiteit.')
            ->assertSee('Bekijk');
    }

    public function test_suggested_tags_are_auto_selected_from_processing(): void
    {
        $user = User::factory()->create();

        $themeTag = Tag::factory()->theme()->create(['slug' => 'muziek']);
        $goalTag = Tag::factory()->goal()->create(['slug' => 'doel-doen']);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-tags-key')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-tags-key', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => [
                'description' => 'Samenvatting',
                'preparation' => '',
                'inventory' => '',
                'process' => '',
                'duration_estimate' => '',
                'group_size_estimate' => '',
                'suggested_goals' => ['doen'],
                'suggested_themes' => ['muziek'],
            ],
            'matched_initiatives' => null,
        ], 3600);

        $component->call('checkProcessing');

        $this->assertContains($themeTag->id, $component->get('selectedThemeTags'));
        $this->assertContains($themeTag->id, $component->get('suggestedThemeTagIds'));
        $this->assertContains($goalTag->id, $component->get('selectedGoalTags'));
        $this->assertContains($goalTag->id, $component->get('suggestedGoalTagIds'));
    }

    public function test_matched_initiatives_are_loaded_from_processing(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('processingKey', 'test-init-key')
            ->set('processingStep', 'analyzing');

        Cache::put('fiche-processing:test-init-key', [
            'step' => 'done',
            'updated_at' => now()->timestamp,
            'analysis' => null,
            'matched_initiatives' => [
                'matched_initiative_ids' => [$initiative->id],
                'match_reasons' => ['Past goed bij het thema'],
            ],
        ], 3600);

        $component->call('checkProcessing');

        $matchedInitiatives = $component->get('matchedInitiatives');
        $this->assertCount(1, $matchedInitiatives);
        $this->assertEquals($initiative->id, $matchedInitiatives[0]['id']);
        $this->assertEquals($initiative->id, $component->get('selectedInitiativeId'));
    }

    public function test_subsequent_file_uploads_redispatch_processing(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')]);

        Queue::assertPushed(ProcessFicheUploads::class, 1);

        $component->set('uploads', [UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')]);

        // Should dispatch ProcessFicheUploads a second time (not ExtractFileText)
        Queue::assertPushed(ProcessFicheUploads::class, 2);
        Queue::assertNotPushed(ExtractFileText::class);

        // Second dispatch should have all file IDs and null previewFileId
        $allFileIds = collect($component->get('uploadedFiles'))->pluck('id')->toArray();
        Queue::assertPushed(ProcessFicheUploads::class, function ($job) use ($allFileIds) {
            return $job->fileIds === $allFileIds && $job->previewFileId === null;
        });

        // Processing state should be reset
        $this->assertFalse($component->get('processingComplete'));
        $this->assertEquals('extracting', $component->get('processingStep'));
    }

    public function test_subsequent_uploads_clear_ai_suggestions(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')]);

        // Simulate AI suggestions populated from first processing + user applied them
        $component->set('aiTitle', 'AI Titel')
            ->set('aiDescription', 'AI Beschrijving')
            ->set('aiPreparation', '<p>Voorbereiding</p>')
            ->set('aiInventory', '<p>Inventaris</p>')
            ->set('aiProcess', '<p>Werkwijze</p>')
            ->set('aiDuration', '30 minuten')
            ->set('aiGroupSize', '10-15')
            ->set('matchedInitiatives', [['id' => 1, 'title' => 'Init', 'reason' => 'Match']])
            ->set('aiAnalysis', ['description' => 'test'])
            ->set('suggestedThemeTagIds', [1, 2])
            ->set('suggestedGoalTagIds', [3, 4])
            ->set('preparation', '<p>Applied quiz preparation</p>')
            ->set('inventory', '<p>Applied quiz inventory</p>')
            ->set('process', '<p>Applied quiz process</p>')
            ->set('description', 'Applied quiz description')
            ->set('processingComplete', true)
            ->set('processingStep', 'done');

        // Upload more files
        $component->set('uploads', [UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')]);

        // All AI suggestions should be cleared
        $this->assertNull($component->get('aiTitle'));
        $this->assertNull($component->get('aiDescription'));
        $this->assertNull($component->get('aiPreparation'));
        $this->assertNull($component->get('aiInventory'));
        $this->assertNull($component->get('aiProcess'));
        $this->assertNull($component->get('aiDuration'));
        $this->assertNull($component->get('aiGroupSize'));
        $this->assertEmpty($component->get('matchedInitiatives'));
        $this->assertNull($component->get('aiAnalysis'));
        $this->assertEmpty($component->get('suggestedThemeTagIds'));
        $this->assertEmpty($component->get('suggestedGoalTagIds'));

        // User content fields should also be cleared (stale from previous file)
        $this->assertEmpty($component->get('preparation'));
        $this->assertEmpty($component->get('inventory'));
        $this->assertEmpty($component->get('process'));
        $this->assertEmpty($component->get('description'));
    }

    public function test_subsequent_uploads_reset_processing_state(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')]);

        // Simulate first processing completed
        $component->set('processingStep', 'done')
            ->set('processingComplete', true);

        // Upload more files
        $component->set('uploads', [UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')]);

        // Processing state should be reset to active
        $this->assertEquals('extracting', $component->get('processingStep'));
        $this->assertFalse($component->get('processingComplete'));
        $this->assertFalse($component->get('processingStale'));
    }

    public function test_subsequent_uploads_include_all_file_ids_in_processing(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        // Upload batch 1: 2 files
        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
            ]);

        $this->assertCount(2, $component->get('uploadedFiles'));

        // Upload batch 2: 1 more file
        $component->set('uploads', [UploadedFile::fake()->create('file3.pdf', 100, 'application/pdf')]);

        $allFileIds = collect($component->get('uploadedFiles'))->pluck('id')->toArray();
        $this->assertCount(3, $allFileIds);

        // Second ProcessFicheUploads dispatch should contain all 3 file IDs
        Queue::assertPushed(ProcessFicheUploads::class, function ($job) use ($allFileIds) {
            return $job->fileIds === $allFileIds && $job->previewFileId === null;
        });
    }

    public function test_removing_preview_file_with_remaining_dispatches_preview(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'),
            ]);

        $uploadedFiles = $component->get('uploadedFiles');
        $previewFileId = $component->get('previewFileId');
        $this->assertEquals($uploadedFiles[0]['id'], $previewFileId);

        Queue::assertPushed(ProcessFicheUploads::class, 1);

        $component->call('removeFile', $previewFileId);

        $newPreviewFileId = $component->get('previewFileId');
        $this->assertNotEquals($previewFileId, $newPreviewFileId);
        $this->assertNotNull($newPreviewFileId);

        // With only 1 file remaining, auto-selects + dispatches preview generation
        $this->assertCount(1, $component->get('uploadedFiles'));
        Queue::assertPushed(GenerateFilePreview::class);
    }

    public function test_preview_badge_shown(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $component->assertSee('Preview');
    }

    public function test_processing_progress_visible_inline_after_upload(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $component->assertSet('currentStep', 1);
        $component->assertSet('processingStep', 'extracting');
        $component->assertSee('Opladen');
        $component->assertSee('Tekst uitlezen');
        $component->assertSee('Suggesties formuleren');
        $component->assertDontSee('Verwerking');
    }

    public function test_restore_uploaded_files_sets_preview_file_id(): void
    {
        $user = User::factory()->create();
        $file1 = File::factory()->create(['fiche_id' => null]);
        $file2 = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('restoreUploadedFiles', [$file1->id, $file2->id], $file2->id);

        $this->assertEquals($file2->id, $component->get('previewFileId'));
    }

    // ===== New tests for preview file picker =====

    public function test_single_file_auto_selects_preview_no_modal(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('only-file.pdf', 100, 'application/pdf')]);

        $this->assertNotNull($component->get('previewFileId'));
        $this->assertFalse($component->get('showPreviewFileModal'));
    }

    public function test_multiple_files_show_preview_modal(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
            ]);

        $this->assertTrue($component->get('showPreviewFileModal'));
        $this->assertNotNull($component->get('previewFileId'));
    }

    public function test_adding_files_to_existing_shows_modal_again(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')]);

        $this->assertFalse($component->get('showPreviewFileModal'));

        $component->set('uploads', [UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')]);

        $this->assertTrue($component->get('showPreviewFileModal'));
    }

    public function test_confirm_preview_file_closes_modal_and_dispatches_preview(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
            ]);

        $this->assertTrue($component->get('showPreviewFileModal'));

        $uploadedFiles = $component->get('uploadedFiles');
        $component->set('previewFileId', $uploadedFiles[1]['id']);
        $component->call('confirmPreviewFile');

        $this->assertFalse($component->get('showPreviewFileModal'));
        Queue::assertPushed(GenerateFilePreview::class);
    }

    public function test_removing_preview_file_with_two_plus_remaining_shows_modal(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file3.pdf', 100, 'application/pdf'),
            ]);

        $previewFileId = $component->get('previewFileId');
        $component->set('showPreviewFileModal', false);

        $component->call('removeFile', $previewFileId);

        $this->assertTrue($component->get('showPreviewFileModal'));
        $this->assertCount(2, $component->get('uploadedFiles'));
    }

    public function test_removing_non_preview_file_keeps_selection(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
            ]);

        $previewFileId = $component->get('previewFileId');
        $uploadedFiles = $component->get('uploadedFiles');
        $otherFileId = $uploadedFiles[1]['id'];

        $component->set('showPreviewFileModal', false);
        $component->call('removeFile', $otherFileId);

        $this->assertEquals($previewFileId, $component->get('previewFileId'));
        $this->assertFalse($component->get('showPreviewFileModal'));
    }

    public function test_removing_all_files_resets_processing(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('only.pdf', 100, 'application/pdf')]);

        $previewFileId = $component->get('previewFileId');
        $this->assertNotNull($previewFileId);
        $this->assertNotEquals('idle', $component->get('processingStep'));

        $component->call('removeFile', $previewFileId);

        $this->assertNull($component->get('previewFileId'));
        $this->assertEquals('idle', $component->get('processingStep'));
        $this->assertFalse($component->get('processingComplete'));
        $this->assertEmpty($component->get('uploadedFiles'));
    }

    public function test_process_fiche_uploads_dispatched_with_all_file_ids(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
            ]);

        $uploadedFiles = $component->get('uploadedFiles');
        $expectedIds = array_column($uploadedFiles, 'id');

        Queue::assertPushed(ProcessFicheUploads::class, function ($job) use ($expectedIds) {
            return $job->fileIds === $expectedIds;
        });
    }

    public function test_restore_handles_old_main_file_id_format(): void
    {
        $user = User::factory()->create();
        $file1 = File::factory()->create(['fiche_id' => null]);
        $file2 = File::factory()->create(['fiche_id' => null]);

        // Simulate old draft restore with mainFileId parameter
        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('restoreUploadedFiles', [$file1->id, $file2->id], $file1->id);

        $this->assertEquals($file1->id, $component->get('previewFileId'));
        $this->assertCount(2, $component->get('uploadedFiles'));
    }

    // ==========================================
    // Dev Mode Tests
    // ==========================================

    public function test_dev_mode_disabled_blocks_forward_navigation(): void
    {
        $user = User::factory()->admin()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->assertSet('devMode', false)
            ->call('goToStep', 3)
            ->assertSet('currentStep', 1);
    }

    public function test_dev_mode_admin_can_jump_to_step3(): void
    {
        Feature::define('wizard-dev-mode', true);

        $user = User::factory()->admin()->create();
        Tag::factory()->create(['type' => 'theme', 'slug' => 'muziek', 'name' => 'Muziek']);
        Tag::factory()->create(['type' => 'goal', 'slug' => 'doel-doen', 'name' => 'Doen']);
        Initiative::factory()->published()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->assertSet('devMode', true)
            ->call('goToStep', 3)
            ->assertSet('currentStep', 3)
            ->assertSet('processingComplete', true)
            ->assertSet('processingStep', 'done');

        $this->assertNotEmpty($component->get('title'));
        $this->assertNotEmpty($component->get('description'));
        $this->assertNotNull($component->get('aiPreparation'));
        $this->assertNotNull($component->get('aiProcess'));
        $this->assertNotNull($component->get('selectedInitiativeId'));
        $this->assertNotEmpty($component->get('matchedInitiatives'));
    }

    public function test_dev_mode_non_admin_still_blocked(): void
    {
        Feature::define('wizard-dev-mode', true);

        $user = User::factory()->create(['role' => 'contributor']);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->assertSet('devMode', false)
            ->call('goToStep', 3)
            ->assertSet('currentStep', 1);
    }

    public function test_dev_mode_does_not_overwrite_existing_input(): void
    {
        Feature::define('wizard-dev-mode', true);

        $user = User::factory()->admin()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Mijn eigen titel')
            ->set('description', 'Mijn beschrijving')
            ->call('goToStep', 2)
            ->assertSet('title', 'Mijn eigen titel')
            ->assertSet('description', 'Mijn beschrijving');
    }

    public function test_dev_mode_jump_to_step2(): void
    {
        Feature::define('wizard-dev-mode', true);

        $user = User::factory()->admin()->create();
        Initiative::factory()->published()->create();

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->call('goToStep', 2)
            ->assertSet('currentStep', 2)
            ->assertSet('processingComplete', true);

        $this->assertNotEmpty($component->get('matchedInitiatives'));
        $this->assertNull($component->get('aiPreparation'));
    }

    // ==========================================
    // Session Persistence Tests
    // ==========================================

    public function test_session_cleared_after_publish(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Publiceer test')
            ->set('description', 'Test beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('currentStep', 3)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('publish');

        // Properties are reset to defaults after save
        $component->assertSet('title', '')
            ->assertSet('description', '')
            ->assertSet('uploadedFiles', [])
            ->assertSet('previewFileId', null);
    }

    public function test_session_cleared_after_save_draft(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $file = File::factory()->create(['fiche_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Concept test')
            ->set('description', 'Test beschrijving')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('currentStep', 3)
            ->set('uploadedFiles', [['id' => $file->id, 'name' => $file->original_filename, 'size' => $file->size_bytes, 'type' => 'PDF']])
            ->call('saveDraft');

        // Properties are reset to defaults after save
        $component->assertSet('title', '')
            ->assertSet('description', '')
            ->assertSet('uploadedFiles', []);
    }

    public function test_session_restore_filters_deleted_files(): void
    {
        $user = User::factory()->create();
        $existingFile = File::factory()->create(['fiche_id' => null]);
        $deletedFileId = 99999;

        session([
            'fiche-wizard.uploadedFiles' => [
                ['id' => $existingFile->id, 'name' => $existingFile->original_filename, 'size' => $existingFile->size_bytes, 'type' => 'PDF'],
                ['id' => $deletedFileId, 'name' => 'deleted.pdf', 'size' => 1000, 'type' => 'PDF'],
            ],
            'fiche-wizard.previewFileId' => $existingFile->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class);

        $uploadedFiles = $component->get('uploadedFiles');
        $this->assertCount(1, $uploadedFiles);
        $this->assertEquals($existingFile->id, $uploadedFiles[0]['id']);
    }

    public function test_session_restore_filters_claimed_files(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        $claimedFile = File::factory()->create(['fiche_id' => $fiche->id]);
        $unclaimedFile = File::factory()->create(['fiche_id' => null]);

        session([
            'fiche-wizard.uploadedFiles' => [
                ['id' => $claimedFile->id, 'name' => $claimedFile->original_filename, 'size' => $claimedFile->size_bytes, 'type' => 'PDF'],
                ['id' => $unclaimedFile->id, 'name' => $unclaimedFile->original_filename, 'size' => $unclaimedFile->size_bytes, 'type' => 'PDF'],
            ],
            'fiche-wizard.previewFileId' => $unclaimedFile->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class);

        $uploadedFiles = $component->get('uploadedFiles');
        $this->assertCount(1, $uploadedFiles);
        $this->assertEquals($unclaimedFile->id, $uploadedFiles[0]['id']);
    }

    public function test_stale_preview_file_falls_back(): void
    {
        $user = User::factory()->create();
        $validFile = File::factory()->create(['fiche_id' => null]);
        $deletedFileId = 99999;

        session([
            'fiche-wizard.uploadedFiles' => [
                ['id' => $validFile->id, 'name' => $validFile->original_filename, 'size' => $validFile->size_bytes, 'type' => 'PDF'],
                ['id' => $deletedFileId, 'name' => 'deleted.pdf', 'size' => 1000, 'type' => 'PDF'],
            ],
            'fiche-wizard.previewFileId' => $deletedFileId,
        ]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class);

        $this->assertEquals($validFile->id, $component->get('previewFileId'));
    }

    public function test_draft_banner_no_longer_shown(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->assertDontSee('Er is een eerder ingevuld concept gevonden');
    }

    // ==========================================
    // Similar Fiches Tests
    // ==========================================

    public function test_similar_fiches_found_by_title_match(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekbingo', 'initiative_id' => $initiative->id]);
        Fiche::factory()->published()->create(['title' => 'Dierenbingo', 'initiative_id' => $initiative->id]);
        Fiche::factory()->published()->create(['title' => 'Voelbingo', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Bingo');

        $similarFiches = $component->get('similarFiches');
        $this->assertEquals(3, $similarFiches['count']);
        $this->assertCount(3, $similarFiches['examples']);
        $this->assertEquals('bingo', $similarFiches['keyword']);
    }

    public function test_similar_fiches_empty_for_short_title(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'ab')
            ->assertSet('similarFiches', []);
    }

    public function test_similar_fiches_empty_for_no_match(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Xyzzyblargh')
            ->assertSet('similarFiches', []);
    }

    public function test_similar_fiches_excludes_unpublished(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekbingo', 'initiative_id' => $initiative->id]);
        Fiche::factory()->create(['title' => 'Dierenbingo', 'initiative_id' => $initiative->id, 'published' => false]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Bingo');

        $similarFiches = $component->get('similarFiches');
        $this->assertEquals(1, $similarFiches['count']);
    }

    public function test_similar_fiches_singular_text_for_one_result(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekbingo', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Bingo');

        $similarFiches = $component->get('similarFiches');
        $this->assertEquals(1, $similarFiches['count']);
        $this->assertCount(1, $similarFiches['examples']);
    }

    public function test_similar_fiches_limits_examples_to_three(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        for ($i = 1; $i <= 5; $i++) {
            Fiche::factory()->published()->create(['title' => "Bingo variant {$i}", 'initiative_id' => $initiative->id]);
        }

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Bingo');

        $similarFiches = $component->get('similarFiches');
        $this->assertEquals(5, $similarFiches['count']);
        $this->assertCount(3, $similarFiches['examples']);
    }

    public function test_similar_fiches_triggered_from_filename_autotitle(): void
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekbingo', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('muziekbingo.pdf', 100, 'application/pdf')]);

        $similarFiches = $component->get('similarFiches');
        $this->assertNotEmpty($similarFiches);
        $this->assertGreaterThanOrEqual(1, $similarFiches['count']);
    }

    public function test_similar_fiches_tip_visible_in_step2(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekbingo', 'initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', 'Bingo')
            ->assertSeeHtml('Er bestaat al <strong>1 bingo-fiche</strong>');
    }

    public function test_similar_fiches_tip_hidden_when_no_results(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('title', 'Xyzzyblargh')
            ->assertDontSee('Er bestaan al')
            ->assertDontSee('Er bestaat al');
    }

    public function test_similar_fiches_word_based_search_finds_matches_for_multi_word_title(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekquiz', 'initiative_id' => $initiative->id]);
        Fiche::factory()->published()->create(['title' => 'Pubquiz jaren 60', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Mijn quiz');

        $similarFiches = $component->get('similarFiches');
        $this->assertEquals(2, $similarFiches['count']);
        $this->assertEquals('quiz', $similarFiches['keyword']);
    }

    public function test_similar_fiches_filters_dutch_stop_words(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Wandeling door het park', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Mijn nieuwe wandeling');

        $similarFiches = $component->get('similarFiches');
        $this->assertEquals(1, $similarFiches['count']);
        $this->assertEquals('wandeling', $similarFiches['keyword']);
    }

    public function test_similar_fiches_empty_when_only_stop_words(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Mijn eigen nieuwe')
            ->assertSet('similarFiches', []);
    }

    public function test_similar_fiches_picks_word_with_most_matches_as_keyword(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Muziekbingo met slagermuziek', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Leuke muziekbingo');

        $similarFiches = $component->get('similarFiches');
        $this->assertNotEmpty($similarFiches);
        $this->assertEquals('muziekbingo', $similarFiches['keyword']);
    }

    public function test_similar_fiches_keyword_matches_examples_for_multi_word_title(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        // 3 quiz fiches
        Fiche::factory()->published()->create(['title' => 'Carnaval Quiz', 'initiative_id' => $initiative->id]);
        Fiche::factory()->published()->create(['title' => 'Valentijnsquiz', 'initiative_id' => $initiative->id]);
        Fiche::factory()->published()->create(['title' => 'Eurosongquiz', 'initiative_id' => $initiative->id]);

        // 1 gezonde fiche
        Fiche::factory()->published()->create(['title' => 'Gezonde smoothies', 'initiative_id' => $initiative->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Quiz gezonde voeding');

        $similarFiches = $component->get('similarFiches');
        $this->assertNotEmpty($similarFiches);
        // Should pick "quiz" (3 matches) over "gezonde" (1 match) or "voeding" (0 matches)
        $this->assertEquals('quiz', $similarFiches['keyword']);
        $this->assertEquals(3, $similarFiches['count']);
    }

    public function test_similar_fiches_filters_words_shorter_than_three_characters(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()->published()->create(['title' => 'Bingo op woensdag', 'initiative_id' => $initiative->id]);

        // "Op" (2 chars) and "de" (2 chars) should be filtered, only "bingo" remains
        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Op de bingo');

        $similarFiches = $component->get('similarFiches');
        $this->assertNotEmpty($similarFiches);
        $this->assertEquals('bingo', $similarFiches['keyword']);
    }

    public function test_similar_fiches_populated_on_submit_step1(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->count(2)->create(['title' => 'Bingo avond', 'published' => true]);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('title', 'Bingo')
            ->call('submitStep1');

        $similarFiches = $component->get('similarFiches');
        $this->assertNotEmpty($similarFiches);
        $this->assertEquals(2, $similarFiches['count']);
        $this->assertEquals('bingo', $similarFiches['keyword']);
        $component->assertSet('currentStep', 2);
    }

    public function test_initiative_dropdown_shows_iets_anders_when_ai_suggestions_exist(): void
    {
        $user = User::factory()->create();
        $matched = Initiative::factory()->published()->create(['title' => 'Quiz']);
        Initiative::factory()->published()->create(['title' => 'Beweging en fit']);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('processingComplete', true)
            ->set('matchedInitiatives', [['id' => $matched->id, 'title' => $matched->title, 'reason' => 'Past bij je activiteit']])
            ->set('selectedInitiativeId', $matched->id)
            ->assertSee('Iets anders...')
            ->assertDontSee('Kies een initiatief...');
    }

    public function test_initiative_dropdown_shows_kies_placeholder_without_ai_suggestions(): void
    {
        $user = User::factory()->create();
        Initiative::factory()->published()->create(['title' => 'Beweging en fit']);

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('processingComplete', true)
            ->set('matchedInitiatives', [])
            ->assertSee('Kies een initiatief...')
            ->assertDontSee('Iets anders...');
    }

    public function test_initiative_dropdown_excludes_matched_initiatives(): void
    {
        $user = User::factory()->create();
        $matched = Initiative::factory()->published()->create(['title' => 'Quiz activiteit']);
        Initiative::factory()->published()->create(['title' => 'Beweging en fit']);

        $component = Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 2)
            ->set('processingComplete', true)
            ->set('matchedInitiatives', [['id' => $matched->id, 'title' => $matched->title, 'reason' => 'Match']])
            ->set('selectedInitiativeId', $matched->id);

        $html = $component->html();

        // The matched initiative appears once in the radio card, but should NOT appear in the listbox options
        // Listbox options use data-flux-select-option attribute
        preg_match_all('/data-flux-select-option[^>]*>/', $html, $optionMatches);
        $optionHtml = implode(' ', $optionMatches[0]);
        $this->assertStringNotContainsString('Quiz activiteit', $optionHtml, 'Matched initiative should not appear as a dropdown option');

        // The non-matched initiative should be in the dropdown
        $component->assertSee('Beweging en fit');
    }

    public function test_ai_suggestions_are_persisted_on_save(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('currentStep', 3)
            ->set('title', 'Muziekbingo')
            ->set('description', '<p>Een leuke activiteit.</p>')
            ->set('selectedInitiativeId', $initiative->id)
            ->set('aiTitle', 'Muziekbingo met schlagers uit de jaren 60')
            ->set('aiDescription', '<p>AI description</p>')
            ->set('aiPreparation', '<p>AI preparation</p>')
            ->set('aiInventory', null)
            ->set('aiProcess', null)
            ->set('aiDuration', '30 min')
            ->set('aiGroupSize', '4-8')
            ->set('aiAnalysis', ['some' => 'data'])
            ->set('appliedSuggestions', ['description'])
            ->call('publish');

        $fiche = Fiche::where('title', 'Muziekbingo')->first();
        $this->assertNotNull($fiche);
        $this->assertNotNull($fiche->ai_suggestions);
        $this->assertEquals('Muziekbingo met schlagers uit de jaren 60', $fiche->ai_suggestions['title']);
        $this->assertEquals('<p>AI description</p>', $fiche->ai_suggestions['description']);
        $this->assertEquals(['description'], $fiche->ai_suggestions['applied']);
    }
}
