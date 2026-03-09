<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_meta_description(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('<meta name="description"', false);
    }

    public function test_home_page_has_open_graph_tags(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:image"', false);
        $response->assertSee('<meta property="og:url"', false);
        $response->assertSee('<meta property="og:type"', false);
    }

    public function test_guest_layout_has_meta_description(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('<meta name="description"', false);
    }

    public function test_guest_layout_has_open_graph_tags(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:image"', false);
    }

    public function test_home_page_has_canonical_url(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('<link rel="canonical"', false);
    }

    public function test_sitemap_returns_xml(): void
    {
        Feature::define('diamant-goals', true);

        $initiative = Initiative::factory()->create(['published' => true]);
        Fiche::factory()->create(['initiative_id' => $initiative->id, 'published' => true]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<urlset', false);
        $response->assertSee('<loc>', false);
        $response->assertSee($initiative->slug, false);
    }

    public function test_home_page_has_organization_json_ld(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('"@type": "Organization"', false);
    }

    public function test_fiche_page_has_howto_json_ld(): void
    {
        $initiative = Initiative::factory()->create(['published' => true]);
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'published' => true,
            'materials' => ['preparation' => 'Stoelen klaarzetten', 'process' => 'Samen zingen'],
        ]);

        $this->get(route('fiches.show', [$initiative, $fiche]))
            ->assertStatus(200)
            ->assertSee('"@type": "HowTo"', false)
            ->assertSee('"@type": "HowToStep"', false);
    }

    public function test_sitemap_excludes_unpublished_initiatives(): void
    {
        Initiative::factory()->create(['published' => false, 'title' => 'Geheim initiatief']);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertDontSee('Geheim initiatief', false);
    }
}
