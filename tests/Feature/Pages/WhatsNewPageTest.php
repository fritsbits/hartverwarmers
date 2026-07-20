<?php

namespace Tests\Feature\Pages;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsNewPageTest extends TestCase
{
    use RefreshDatabase;

    private function putUpdate(string $uid, string $publishedAt, string $title, ?array $link = null, array $overrides = []): void
    {
        Storage::disk('content')->put("updates/{$uid}.json", json_encode(array_filter(array_merge([
            'uid' => $uid,
            'published_at' => $publishedAt,
            'title' => $title,
            'body' => 'Korte tekst voor de test.',
            'link' => $link,
        ], $overrides))));
    }

    public function test_whats_new_page_loads(): void
    {
        Storage::fake('content');

        $this->get('/wat-is-er-nieuw')->assertStatus(200);
    }

    public function test_whats_new_page_lists_updates_newest_first_with_a_date_stamp(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-03-website', '2026-03-19', 'Vernieuwde website');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af', ['url' => '/themas', 'label' => 'Bekijk de themakalender']);

        $response = $this->get('/wat-is-er-nieuw');

        $response->assertSeeInOrder(['Druk de themakalender af', 'Vernieuwde website']);
        $response->assertSeeInOrder(['donderdag', '16', 'juli'], false);
        $response->assertSeeInOrder(['donderdag', '19', 'maart'], false);
    }

    public function test_whats_new_page_links_each_update_to_its_own_page(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-03-website', '2026-03-19', 'Vernieuwde website');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af');

        $this->get('/wat-is-er-nieuw')
            ->assertSee(route('whats-new.show', '2026-07-kalender'))
            ->assertSee(route('whats-new.show', '2026-03-website'));
    }

    public function test_whats_new_page_shows_empty_state_without_updates(): void
    {
        Storage::fake('content');

        $this->get('/wat-is-er-nieuw')->assertSee('Nog geen updates');
    }

    public function test_update_page_shows_the_teaser_the_rendered_content_and_the_action_link(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af', ['url' => '/themas', 'label' => 'Bekijk de themakalender'], [
            'body' => 'De korte teaser.',
            'content' => "## Zo druk je het af\n\nKlik op **Print**.",
        ]);

        $this->get(route('whats-new.show', '2026-07-kalender'))
            ->assertOk()
            ->assertSee('Druk de themakalender af')
            ->assertSee('De korte teaser.')
            ->assertSee('<h2>Zo druk je het af</h2>', false)
            ->assertSee('<strong>Print</strong>', false)
            ->assertSee('Bekijk de themakalender');
    }

    public function test_update_page_renders_without_content_image_or_link(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-kaal', '2026-07-16', 'Kale update');

        $this->get(route('whats-new.show', '2026-07-kaal'))
            ->assertOk()
            ->assertSee('Korte tekst voor de test.');
    }

    public function test_update_page_strips_html_embedded_in_the_content(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-stout', '2026-07-16', 'Stoute update', null, [
            'content' => 'Tekst <script>alert(1)</script> erna.',
        ]);

        $this->get(route('whats-new.show', '2026-07-stout'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_update_page_shows_its_image_when_it_has_one(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-beeld', '2026-07-16', 'Update met beeld', null, [
            'image' => ['src' => '/images/updates/print.webp', 'alt' => 'Het afgedrukte A3-blad'],
        ]);

        $this->get(route('whats-new.show', '2026-07-beeld'))
            ->assertOk()
            ->assertSee('images/updates/print.webp')
            ->assertSee('Het afgedrukte A3-blad');
    }

    public function test_update_page_links_to_both_neighbours(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16', 'Nieuwste');
        $this->putUpdate('2026-06-middelste', '2026-06-10', 'Middelste');
        $this->putUpdate('2026-05-oudste', '2026-05-01', 'Oudste');

        $this->get(route('whats-new.show', '2026-06-middelste'))
            ->assertOk()
            ->assertSee('Vorige update')
            ->assertSee(route('whats-new.show', '2026-05-oudste'))
            ->assertSee('Volgende update')
            ->assertSee(route('whats-new.show', '2026-07-nieuwste'));
    }

    public function test_oldest_update_page_has_no_previous_link(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16', 'Nieuwste');
        $this->putUpdate('2026-05-oudste', '2026-05-01', 'Oudste');

        $this->get(route('whats-new.show', '2026-05-oudste'))
            ->assertOk()
            ->assertDontSee('Vorige update')
            ->assertSee('Volgende update');
    }

    public function test_newest_update_page_has_no_next_link(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16', 'Nieuwste');
        $this->putUpdate('2026-05-oudste', '2026-05-01', 'Oudste');

        $this->get(route('whats-new.show', '2026-07-nieuwste'))
            ->assertOk()
            ->assertSee('Vorige update')
            ->assertDontSee('Volgende update');
    }

    public function test_unknown_update_returns_404(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16', 'Nieuwste');

        $this->get(route('whats-new.show', 'bestaat-niet'))->assertNotFound();
    }

    public function test_sitemap_lists_the_update_pages(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16', 'Nieuwste');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('whats-new.show', '2026-07-nieuwste'), false);
    }

    public function test_footer_links_to_whats_new_page(): void
    {
        $this->get('/')->assertSee('Wat is er nieuw');
    }

    public function test_guest_does_not_see_whats_new_banner_on_homepage(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
        $response->assertDontSee('Ontdek wat er nieuw is');
    }

    public function test_existing_user_does_not_see_whats_new_banner(): void
    {
        $user = User::factory()->create([
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))->subDay(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
    }

    public function test_new_user_does_not_see_whats_new_banner(): void
    {
        $user = User::factory()->create([
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
    }

    public function test_new_user_sees_onboarding_banner_instead(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => null,
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
        $response->assertSee('Dit kan je nu allemaal');
    }
}
