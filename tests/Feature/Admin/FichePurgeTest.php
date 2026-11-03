<?php

namespace Tests\Feature\Admin;

use App\Livewire\AdminFicheOverview;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\File;
use App\Models\FileUpload;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\PendingNotification;
use App\Models\Tag;
use App\Models\Theme;
use App\Models\User;
use App\Services\FichePurger;
use App\Services\FicheStorage\MigrationRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Flysystem\UnableToDeleteFile;
use Livewire\Livewire;
use Tests\Concerns\FakesFicheStorage;
use Tests\TestCase;

class FichePurgeTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage();
    }

    /**
     * A fiche with one uploaded PDF, two preview images plus their thumbnails,
     * and a zip — the shape a real migrated fiche has.
     */
    private function ficheWithArtifacts(?Initiative $initiative = null): Fiche
    {
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => ($initiative ?? Initiative::factory()->published()->create())->id,
            'zip_path' => 'fiche-zips/actuaquiz.zip',
        ]);

        $file = File::factory()->create([
            'fiche_id' => $fiche->id,
            'path' => 'files/media/4506/Actuaquiz-2023.pdf',
            'preview_images' => [
                'file-previews/4506/slide-001.jpg',
                'file-previews/4506/slide-002.jpg',
            ],
            'total_slides' => 43,
        ]);

        $this->localFiles()->put($file->path, 'pdf-bytes');
        $this->localFiles()->put($fiche->zip_path, 'zip-bytes');

        foreach ($file->preview_images as $preview) {
            $this->localFiles()->put($preview, 'jpg-bytes');
        }
        foreach ($file->thumbnailPaths() as $thumb) {
            $this->localFiles()->put($thumb, 'jpg-bytes');
        }

        return $fiche->fresh();
    }

    public function test_purge_removes_the_file_previews_thumbnails_and_zip_from_disk(): void
    {
        $fiche = $this->ficheWithArtifacts();

        app(FichePurger::class)->purge($fiche);

        $this->localFiles()->assertMissing('files/media/4506/Actuaquiz-2023.pdf');
        $this->localFiles()->assertMissing('file-previews/4506/slide-001.jpg');
        $this->localFiles()->assertMissing('file-previews/4506/slide-002.jpg');
        $this->localFiles()->assertMissing('file-previews/4506/slide-001-thumb.jpg');
        $this->localFiles()->assertMissing('file-previews/4506/slide-002-thumb.jpg');
        $this->localFiles()->assertMissing('fiche-zips/actuaquiz.zip');
    }

    public function test_purge_removes_the_emptied_media_directory(): void
    {
        $fiche = $this->ficheWithArtifacts();

        app(FichePurger::class)->purge($fiche);

        $this->assertNotContains(
            'files/media/4506',
            $this->localFiles()->directories('files/media'),
        );
    }

    public function test_purge_keeps_a_directory_that_still_holds_another_fiches_file(): void
    {
        $fiche = $this->ficheWithArtifacts();
        $this->localFiles()->put('files/media/4506/andere-fiche.pdf', 'pdf-bytes');

        app(FichePurger::class)->purge($fiche);

        $this->localFiles()->assertExists('files/media/4506/andere-fiche.pdf');
    }

    public function test_purge_hard_deletes_the_fiche_and_its_file_rows(): void
    {
        $fiche = $this->ficheWithArtifacts();
        $fileId = $fiche->files->first()->id;

        app(FichePurger::class)->purge($fiche);

        $this->assertDatabaseMissing('fiches', ['id' => $fiche->id]);
        $this->assertDatabaseMissing('files', ['id' => $fileId]);
    }

    public function test_purge_also_removes_generated_pdfs_of_the_source_file(): void
    {
        $fiche = Fiche::factory()->published()->create();
        $source = File::factory()->pptx()->create(['fiche_id' => $fiche->id]);
        $generated = File::factory()->generatedPdf($source)->create();

        $this->localFiles()->put($source->path, 'pptx-bytes');
        $this->localFiles()->put($generated->path, 'pdf-bytes');

        app(FichePurger::class)->purge($fiche->fresh());

        $this->localFiles()->assertMissing($source->path);
        $this->localFiles()->assertMissing($generated->path);
        $this->assertDatabaseMissing('files', ['id' => $generated->id]);
    }

    public function test_purge_removes_comments_likes_tags_themes_and_pending_notifications(): void
    {
        $fiche = $this->ficheWithArtifacts();

        $comment = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);
        Comment::factory()->reply($comment)->create();

        $like = Like::factory()->kudos(3)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        $tag = Tag::factory()->theme()->create();
        $fiche->tags()->attach($tag);

        $theme = Theme::factory()->create();
        $fiche->themes()->attach($theme);

        $notification = PendingNotification::create([
            'user_id' => User::factory()->create()->id,
            'fiche_id' => $fiche->id,
            'type' => 'comment',
            'payload' => [],
        ]);

        app(FichePurger::class)->purge($fiche);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('likes', ['id' => $like->id]);
        $this->assertDatabaseMissing('taggables', ['taggable_type' => Fiche::class, 'taggable_id' => $fiche->id]);
        $this->assertDatabaseMissing('fiche_theme', ['fiche_id' => $fiche->id]);
        $this->assertDatabaseMissing('pending_notifications', ['id' => $notification->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_purge_removes_the_upload_audit_rows_of_its_files(): void
    {
        $fiche = $this->ficheWithArtifacts();
        $upload = FileUpload::factory()->create(['file_id' => $fiche->files->first()->id]);

        app(FichePurger::class)->purge($fiche);

        $this->assertDatabaseMissing('file_uploads', ['id' => $upload->id]);
    }

    public function test_purge_leaves_the_other_fiches_of_the_same_initiative_untouched(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $target = $this->ficheWithArtifacts($initiative);

        $survivor = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $survivorFile = File::factory()->create(['fiche_id' => $survivor->id]);
        $this->localFiles()->put($survivorFile->path, 'pdf-bytes');

        app(FichePurger::class)->purge($target);

        $this->assertDatabaseHas('fiches', ['id' => $survivor->id]);
        $this->assertDatabaseHas('files', ['id' => $survivorFile->id]);
        $this->assertDatabaseHas('initiatives', ['id' => $initiative->id]);
        $this->localFiles()->assertExists($survivorFile->path);
    }

    public function test_purge_reports_what_it_removed(): void
    {
        $fiche = $this->ficheWithArtifacts();
        Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);
        Like::factory()->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
        ]);

        $summary = app(FichePurger::class)->purge($fiche);

        $this->assertSame(1, $summary['files']);
        $this->assertSame(4, $summary['images']);
        $this->assertSame(1, $summary['comments']);
        $this->assertSame(1, $summary['likes']);
    }

    public function test_purging_a_single_file_also_removes_its_thumbnails(): void
    {
        $fiche = $this->ficheWithArtifacts();
        $file = $fiche->files->first();

        app(FichePurger::class)->purgeFile($file);

        $this->localFiles()->assertMissing($file->path);
        $this->localFiles()->assertMissing('file-previews/4506/slide-001-thumb.jpg');
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id]);
    }

    public function test_purge_survives_artifacts_that_are_already_gone_from_disk(): void
    {
        $fiche = $this->ficheWithArtifacts();
        $this->localFiles()->delete($fiche->files->first()->path);

        $summary = app(FichePurger::class)->purge($fiche);

        $this->assertSame(1, $summary['files']);
        $this->assertDatabaseMissing('fiches', ['id' => $fiche->id]);
    }

    public function test_the_confirmation_modal_spells_out_what_will_be_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = $this->ficheWithArtifacts();
        Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->call('confirmPurge', $fiche->id)
            ->assertSet('showPurgeModal', true)
            ->assertSee($fiche->title)
            ->assertSee($fiche->files->first()->original_filename)
            ->assertSee('1 bestand(en) van de server, inclusief previews en thumbnails')
            ->assertSee('1 reactie(s) en 0 hartje(s)');
    }

    public function test_admin_can_purge_a_fiche_from_the_overview(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = $this->ficheWithArtifacts();

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->call('confirmPurge', $fiche->id)
            ->set('purgeConfirmation', 'VERWIJDER')
            ->call('purge')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('fiches', ['id' => $fiche->id]);
        $this->localFiles()->assertMissing('files/media/4506/Actuaquiz-2023.pdf');
    }

    public function test_purge_does_nothing_without_the_exact_confirmation_word(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = $this->ficheWithArtifacts();

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->call('confirmPurge', $fiche->id)
            ->set('purgeConfirmation', 'verwijder maar')
            ->call('purge')
            ->assertHasErrors('purgeConfirmation');

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id]);
        $this->localFiles()->assertExists('files/media/4506/Actuaquiz-2023.pdf');
    }

    public function test_non_admin_cannot_purge_a_fiche(): void
    {
        $contributor = User::factory()->create();
        $fiche = $this->ficheWithArtifacts();

        Livewire::actingAs($contributor)
            ->test(AdminFicheOverview::class)
            ->call('confirmPurge', $fiche->id)
            ->assertForbidden();

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id]);
    }

    public function test_the_overview_can_list_unpublished_fiches(): void
    {
        $admin = User::factory()->admin()->create();
        $hidden = Fiche::factory()->create(['published' => false, 'title' => 'Verborgen fiche']);

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->set('filter', 'unpublished')
            ->assertSee($hidden->title);
    }

    public function test_purge_trashed_command_purges_soft_deleted_fiches_only(): void
    {
        $trashed = $this->ficheWithArtifacts();
        $trashed->delete();

        $live = Fiche::factory()->published()->create();
        $liveFile = File::factory()->create(['fiche_id' => $live->id]);
        $this->localFiles()->put($liveFile->path, 'pdf-bytes');

        $this->artisan('fiche:purge-trashed --force')->assertSuccessful();

        $this->assertDatabaseMissing('fiches', ['id' => $trashed->id]);
        $this->localFiles()->assertMissing('files/media/4506/Actuaquiz-2023.pdf');

        $this->assertDatabaseHas('fiches', ['id' => $live->id]);
        $this->localFiles()->assertExists($liveFile->path);
    }

    public function test_purge_trashed_command_reports_when_there_is_nothing_to_do(): void
    {
        Fiche::factory()->published()->create();

        $this->artisan('fiche:purge-trashed --force')
            ->expectsOutputToContain('Geen verwijderde fiches gevonden.')
            ->assertSuccessful();
    }

    /**
     * Put the same bytes on the local and the remote disks, as mirror mode does.
     */
    private function mirrorArtifacts(Fiche $fiche): void
    {
        $file = $fiche->files->first();

        $this->remoteFiles()->put($file->path, 'pdf-bytes');

        foreach ([...$file->preview_images, ...$file->thumbnailPaths()] as $preview) {
            $this->remotePreviews()->put($preview, 'jpg-bytes');
        }
    }

    public function test_mirror_purge_removes_every_artifact_from_both_disks_and_all_versions(): void
    {
        $this->fakeFicheStorage('mirror');
        $initiative = Initiative::factory()->published()->create();
        $fiche = $this->ficheWithArtifacts($initiative);
        $this->mirrorArtifacts($fiche);

        $survivor = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $survivorFile = File::factory()->create(['fiche_id' => $survivor->id]);
        $this->localFiles()->put($survivorFile->path, 'keep');
        $this->remoteFiles()->put($survivorFile->path, 'keep');

        app(FichePurger::class)->purge($fiche);

        $original = 'files/media/4506/Actuaquiz-2023.pdf';
        $previews = ['file-previews/4506/slide-001.jpg', 'file-previews/4506/slide-002.jpg', 'file-previews/4506/slide-001-thumb.jpg', 'file-previews/4506/slide-002-thumb.jpg'];

        $this->localFiles()->assertMissing($original);
        $this->remoteFiles()->assertMissing($original);
        $this->localFiles()->assertMissing('fiche-zips/actuaquiz.zip');
        foreach ($previews as $preview) {
            $this->localPreviews()->assertMissing($preview);
            $this->remotePreviews()->assertMissing($preview);
        }

        $this->assertEqualsCanonicalizing([$original, ...$previews], $this->objectVersions->purged);
        $this->localFiles()->assertExists($survivorFile->path);
        $this->remoteFiles()->assertExists($survivorFile->path);
        $this->assertNotContains($survivorFile->path, $this->objectVersions->purged);
    }

    public function test_mirror_purge_marks_the_objects_deleted_in_the_register(): void
    {
        $this->fakeFicheStorage('mirror');
        $fiche = $this->ficheWithArtifacts();
        $this->mirrorArtifacts($fiche);

        app(FichePurger::class)->purge($fiche);

        $register = app(MigrationRegister::class);
        $this->assertSame(MigrationRegister::STATE_DELETED, $register->object('files', 'files/media/4506/Actuaquiz-2023.pdf')->state);
        $this->assertSame(MigrationRegister::STATE_DELETED, $register->object('previews', 'file-previews/4506/slide-001-thumb.jpg')->state);
    }

    public function test_purge_throws_and_keeps_the_rows_when_the_remote_delete_fails(): void
    {
        $this->fakeFicheStorage('mirror');
        $fiche = $this->ficheWithArtifacts();
        $fileId = $fiche->files->first()->id;
        $this->mirrorArtifacts($fiche);
        $this->breakRemote('fiche-files-s3', 'delete');

        $thrown = null;
        try {
            app(FichePurger::class)->purge($fiche);
        } catch (UnableToDeleteFile $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The purge must surface the failed remote delete.');
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id]);
        $this->assertDatabaseHas('files', ['id' => $fileId]);
    }
}
