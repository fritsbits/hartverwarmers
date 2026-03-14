<?php

namespace Tests\Feature;

use App\Livewire\FicheWizard;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FileUploadAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_step1_requires_disclaimer_when_files_uploaded(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')])
            ->call('submitStep1')
            ->assertHasErrors('disclaimerAccepted')
            ->assertSet('currentStep', 1);
    }

    public function test_step1_proceeds_when_disclaimer_accepted(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')])
            ->set('disclaimerAccepted', true)
            ->call('submitStep1')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2);
    }

    public function test_upload_creates_audit_record(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $this->assertDatabaseCount('file_uploads', 1);
        $audit = FileUpload::first();
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertEquals('test.pdf', $audit->original_filename);
        $this->assertNotNull($audit->file_hash);
        $this->assertNotNull($audit->ip_address);
    }

    public function test_disclaimer_backfills_accepted_at_on_submit(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')])
            ->set('disclaimerAccepted', true)
            ->call('submitStep1');

        $audit = FileUpload::first();
        $this->assertNotNull($audit->disclaimer_accepted_at);
    }

    public function test_audit_record_contains_sha256_hash(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $audit = FileUpload::first();
        $this->assertEquals(64, strlen($audit->file_hash));
    }

    public function test_multiple_uploads_create_multiple_audit_records(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 200, 'application/pdf'),
            ]);

        $this->assertDatabaseCount('file_uploads', 2);
    }
}
