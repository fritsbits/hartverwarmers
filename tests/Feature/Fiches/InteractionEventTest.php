<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\InteractionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InteractionEventTest extends TestCase
{
    use RefreshDatabase;

    private Initiative $initiative;

    private Fiche $fiche;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->initiative = Initiative::factory()->published()->create();
        $this->fiche = Fiche::factory()->published()->create([
            'initiative_id' => $this->initiative->id,
        ]);
    }

    public function test_repeated_views_are_each_logged_as_an_event(): void
    {
        $this->actingAs($this->user)->get(route('fiches.show', [$this->initiative, $this->fiche]));
        $this->actingAs($this->user)->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $this->assertSame(2, InteractionEvent::query()
            ->where('user_id', $this->user->id)
            ->where('fiche_id', $this->fiche->id)
            ->where('type', 'view')
            ->count());
    }

    public function test_guest_view_logs_no_event(): void
    {
        $this->get(route('fiches.show', [$this->initiative, $this->fiche]))->assertOk();

        $this->assertSame(0, InteractionEvent::count());
    }

    public function test_repeated_downloads_are_each_logged_as_an_event(): void
    {
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'activiteit.pdf',
            'path' => 'files/test-file.pdf',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk('public')->put($file->path, 'fake pdf content');

        $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));
        $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $this->assertSame(2, InteractionEvent::query()
            ->where('user_id', $this->user->id)
            ->where('fiche_id', $this->fiche->id)
            ->where('type', 'download')
            ->count());
    }

    public function test_event_records_creation_timestamp(): void
    {
        $this->actingAs($this->user)->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $this->assertNotNull(InteractionEvent::sole()->created_at);
    }
}
