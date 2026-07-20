<?php

namespace Tests\Feature\Admin;

use App\Livewire\AdminProductUpdates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductUpdatesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function putUpdate(string $uid, string $publishedAt, string $title, array $overrides = []): void
    {
        Storage::disk('content')->put("updates/{$uid}.json", json_encode(array_filter(array_merge([
            'uid' => $uid,
            'published_at' => $publishedAt,
            'title' => $title,
            'body' => 'Korte tekst voor de test.',
        ], $overrides))));
    }

    public function test_admin_can_access_product_updates(): void
    {
        Storage::fake('content');
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.product-updates.index'));

        $response->assertOk();
        $response->assertSeeLivewire('admin-product-updates');
    }

    public function test_non_admin_cannot_access_product_updates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/product-updates');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_product_updates(): void
    {
        $response = $this->get('/admin/product-updates');

        $response->assertRedirect('/login');
    }

    public function test_overview_explains_the_commit_workflow_outside_production(): void
    {
        Storage::fake('content');

        $this->actingAs($this->createAdmin())
            ->get(route('admin.product-updates.index'))
            ->assertSee('Zo zet je een update online')
            ->assertDontSee('overleven geen deploy');
    }

    public function test_overview_warns_that_changes_are_lost_on_production(): void
    {
        Storage::fake('content');
        $this->app['env'] = 'production';

        $this->actingAs($this->createAdmin())
            ->get(route('admin.product-updates.index'))
            ->assertSee('overleven geen deploy')
            ->assertDontSee('Zo zet je een update online');
    }

    public function test_overview_lists_updates_newest_first(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-03-website', '2026-03-19', 'Vernieuwde website');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af');

        $this->actingAs($this->createAdmin())
            ->get(route('admin.product-updates.index'))
            ->assertSeeInOrder(['Druk de themakalender af', 'Vernieuwde website']);
    }

    public function test_admin_can_create_an_update(): void
    {
        Storage::fake('content');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('uid', '2026-07-nieuwe-functie')
            ->set('publishedAt', '2026-07-20')
            ->set('title', 'Een nieuwe functie')
            ->set('body', 'De korte teaser.')
            ->set('content', "## Zo werkt het\n\nKlik op de knop.")
            ->set('linkUrl', '/themas')
            ->set('linkLabel', 'Bekijk de themakalender')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        Storage::disk('content')->assertExists('updates/2026-07-nieuwe-functie.json');

        $saved = json_decode(Storage::disk('content')->get('updates/2026-07-nieuwe-functie.json'), true);
        $this->assertSame('2026-07-nieuwe-functie', $saved['uid']);
        $this->assertSame('2026-07-20', $saved['published_at']);
        $this->assertSame('Een nieuwe functie', $saved['title']);
        $this->assertSame('De korte teaser.', $saved['body']);
        $this->assertSame("## Zo werkt het\n\nKlik op de knop.", $saved['content']);
        $this->assertSame(['url' => '/themas', 'label' => 'Bekijk de themakalender'], $saved['link']);
        $this->assertArrayNotHasKey('image', $saved);
    }

    public function test_uid_is_derived_from_date_and_title_when_left_empty(): void
    {
        Storage::fake('content');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('publishedAt', '2026-07-20')
            ->set('title', 'Een Nieuwe Functie')
            ->set('body', 'De korte teaser.')
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('content')->assertExists('updates/2026-07-een-nieuwe-functie.json');
    }

    public function test_create_requires_title_and_body(): void
    {
        Storage::fake('content');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('title', '')
            ->set('body', '')
            ->call('save')
            ->assertHasErrors(['title', 'body']);

        $this->assertSame([], Storage::disk('content')->files('updates'));
    }

    public function test_link_label_is_required_when_link_url_is_set(): void
    {
        Storage::fake('content');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('publishedAt', '2026-07-20')
            ->set('title', 'Een nieuwe functie')
            ->set('body', 'De korte teaser.')
            ->set('linkUrl', '/themas')
            ->call('save')
            ->assertHasErrors(['linkLabel']);
    }

    public function test_create_rejects_a_duplicate_uid(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('uid', '2026-07-kalender')
            ->set('publishedAt', '2026-07-20')
            ->set('title', 'Een andere titel')
            ->set('body', 'De korte teaser.')
            ->call('save')
            ->assertHasErrors(['uid']);

        $existing = json_decode(Storage::disk('content')->get('updates/2026-07-kalender.json'), true);
        $this->assertSame('Druk de themakalender af', $existing['title']);
    }

    public function test_create_rejects_an_invalid_uid(): void
    {
        Storage::fake('content');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('uid', 'Ongeldige UID!')
            ->set('publishedAt', '2026-07-20')
            ->set('title', 'Een nieuwe functie')
            ->set('body', 'De korte teaser.')
            ->call('save')
            ->assertHasErrors(['uid']);
    }

    public function test_admin_can_edit_an_update_without_changing_its_uid(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af', [
            'content' => '## Oude inhoud',
            'link' => ['url' => '/themas', 'label' => 'Bekijk de themakalender'],
        ]);

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('edit', '2026-07-kalender')
            ->assertSet('originalUid', '2026-07-kalender')
            ->assertSet('title', 'Druk de themakalender af')
            ->assertSet('content', '## Oude inhoud')
            ->assertSet('linkUrl', '/themas')
            ->set('title', 'Druk de kalender nu af')
            ->set('uid', 'genegeerde-nieuwe-uid')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        Storage::disk('content')->assertMissing('updates/genegeerde-nieuwe-uid.json');

        $saved = json_decode(Storage::disk('content')->get('updates/2026-07-kalender.json'), true);
        $this->assertSame('2026-07-kalender', $saved['uid']);
        $this->assertSame('Druk de kalender nu af', $saved['title']);
        $this->assertSame(['url' => '/themas', 'label' => 'Bekijk de themakalender'], $saved['link']);
    }

    public function test_admin_can_delete_an_update(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('delete', '2026-07-kalender');

        Storage::disk('content')->assertMissing('updates/2026-07-kalender.json');
    }

    public function test_empty_optional_fields_are_omitted_from_the_json(): void
    {
        Storage::fake('content');

        Livewire::actingAs($this->createAdmin())
            ->test(AdminProductUpdates::class)
            ->call('create')
            ->set('publishedAt', '2026-07-20')
            ->set('title', 'Een nieuwe functie')
            ->set('body', 'De korte teaser.')
            ->call('save')
            ->assertHasNoErrors();

        $saved = json_decode(Storage::disk('content')->get('updates/2026-07-een-nieuwe-functie.json'), true);
        $this->assertSame(['uid', 'published_at', 'title', 'body'], array_keys($saved));
    }
}
