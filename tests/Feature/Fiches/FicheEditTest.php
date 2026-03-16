<?php

namespace Tests\Feature\Fiches;

use App\Livewire\FicheEdit;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Tests\TestCase;

class FicheEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $fiche = Fiche::factory()->published()->create();

        $this->get(route('fiches.edit', $fiche))->assertRedirect(route('login'));
    }

    public function test_owner_can_access_edit_page(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('fiches.edit', $fiche))
            ->assertStatus(200)
            ->assertSee('Fiche bewerken');
    }

    public function test_admin_can_access_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = Fiche::factory()->published()->create();

        $this->actingAs($admin)
            ->get(route('fiches.edit', $fiche))
            ->assertStatus(200);
    }

    public function test_other_user_gets_403(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        $this->actingAs($user)
            ->get(route('fiches.edit', $fiche))
            ->assertStatus(403);
    }

    public function test_edit_updates_fiche_fields(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->set('title', 'Nieuwe titel')
            ->set('description', 'Nieuwe beschrijving')
            ->set('preparation', '<p>Stap 1</p>')
            ->set('duration', '30 minuten')
            ->call('save');

        $fiche->refresh();
        $this->assertEquals('Nieuwe titel', $fiche->title);
        $this->assertEquals('Nieuwe beschrijving', $fiche->description);
        $this->assertEquals('<p>Stap 1</p>', $fiche->materials['preparation']);
        $this->assertEquals('30 minuten', $fiche->materials['duration']);
    }

    public function test_edit_syncs_tags(): void
    {
        Feature::define('diamant-goals', true);

        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id]);

        $themeTag = Tag::factory()->theme()->create();
        $goalTag = Tag::factory()->goal()->create();

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->set('selectedThemeTags', [$themeTag->id])
            ->set('selectedGoalTags', [$goalTag->id])
            ->call('save');

        $fiche->refresh();
        $this->assertTrue($fiche->tags->contains($themeTag));
        $this->assertTrue($fiche->tags->contains($goalTag));
    }

    public function test_edit_validates_title_required(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_edit_has_tab_state(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->assertSet('activeTab', 'praktische-informatie');
    }

    public function test_edit_rejects_invalid_file_types_with_dutch_message(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->set('newUploads', [UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload')])
            ->assertHasErrors(['newUploads.*']);

        $this->assertStringContainsString(
            'Dit bestandstype wordt niet ondersteund.',
            implode(' ', $component->errors()->all())
        );
    }

    public function test_edit_rejects_oversized_files_with_dutch_message(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(FicheEdit::class, ['fiche' => $fiche])
            ->set('newUploads', [UploadedFile::fake()->create('large.pdf', 52000, 'application/pdf')])
            ->assertHasErrors(['newUploads.*']);

        $this->assertStringContainsString(
            'Dit bestand is te groot (max 50 MB).',
            // Full message includes advice to resize the file
            implode(' ', $component->errors()->all())
        );
    }
}
