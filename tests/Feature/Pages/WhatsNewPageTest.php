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

    private function putUpdate(string $uid, string $publishedAt, string $title, ?array $link = null): void
    {
        Storage::disk('content')->put("updates/{$uid}.json", json_encode(array_filter([
            'uid' => $uid,
            'published_at' => $publishedAt,
            'title' => $title,
            'body' => 'Korte tekst voor de test.',
            'link' => $link,
        ])));
    }

    public function test_whats_new_page_loads(): void
    {
        Storage::fake('content');

        $this->get('/wat-is-er-nieuw')->assertStatus(200);
    }

    public function test_whats_new_page_lists_updates_newest_first_with_month_labels(): void
    {
        Storage::fake('content');
        $this->putUpdate('2026-03-website', '2026-03-19', 'Vernieuwde website');
        $this->putUpdate('2026-07-kalender', '2026-07-16', 'Druk de themakalender af', ['url' => '/themas', 'label' => 'Bekijk de themakalender']);

        $response = $this->get('/wat-is-er-nieuw');

        $response->assertSeeInOrder(['Druk de themakalender af', 'Vernieuwde website']);
        $response->assertSee('Juli 2026');
        $response->assertSee('Maart 2026');
        $response->assertSee('Bekijk de themakalender');
    }

    public function test_whats_new_page_shows_empty_state_without_updates(): void
    {
        Storage::fake('content');

        $this->get('/wat-is-er-nieuw')->assertSee('Nog geen updates');
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
