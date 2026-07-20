<?php

namespace Tests\Feature\Services;

use App\Services\ProductUpdates;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductUpdatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('content');
    }

    private function putUpdate(string $uid, string $publishedAt, array $overrides = []): void
    {
        Storage::disk('content')->put("updates/{$uid}.json", json_encode(array_merge([
            'uid' => $uid,
            'published_at' => $publishedAt,
            'title' => "Titel {$uid}",
            'body' => 'Korte tekst voor de test.',
        ], $overrides)));
    }

    public function test_all_returns_updates_newest_first(): void
    {
        $this->putUpdate('2026-05-oudste', '2026-05-01');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16');
        $this->putUpdate('2026-06-middelste', '2026-06-10');

        $uids = ProductUpdates::all()->pluck('uid')->all();

        $this->assertSame(['2026-07-nieuwste', '2026-06-middelste', '2026-05-oudste'], $uids);
    }

    public function test_all_skips_invalid_files(): void
    {
        $this->putUpdate('2026-07-geldig', '2026-07-16');
        Storage::disk('content')->put('updates/kapot.json', '{not valid json');
        Storage::disk('content')->put('updates/onvolledig.json', json_encode(['uid' => 'onvolledig', 'title' => 'Geen datum of body']));

        $uids = ProductUpdates::all()->pluck('uid')->all();

        $this->assertSame(['2026-07-geldig'], $uids);
    }

    public function test_all_returns_empty_collection_when_directory_missing(): void
    {
        $this->assertTrue(ProductUpdates::all()->isEmpty());
    }

    public function test_latest_fresh_returns_newest_update_within_60_days(): void
    {
        $now = Carbon::parse('2026-07-16');
        $this->putUpdate('2026-05-te-oud', '2026-05-01');
        $this->putUpdate('2026-07-vers', '2026-07-10');

        $this->assertSame('2026-07-vers', ProductUpdates::latestFresh($now)['uid']);
    }

    public function test_latest_fresh_returns_null_when_newest_is_older_than_60_days(): void
    {
        $now = Carbon::parse('2026-07-16');
        $this->putUpdate('2026-04-te-oud', '2026-04-01');

        $this->assertNull(ProductUpdates::latestFresh($now));
    }

    public function test_latest_fresh_returns_null_when_no_updates_exist(): void
    {
        $this->assertNull(ProductUpdates::latestFresh(Carbon::parse('2026-07-16')));
    }

    public function test_find_returns_the_matching_update(): void
    {
        $this->putUpdate('2026-07-nieuwste', '2026-07-16');
        $this->putUpdate('2026-05-oudste', '2026-05-01');

        $this->assertSame('Titel 2026-05-oudste', ProductUpdates::find('2026-05-oudste')['title']);
    }

    public function test_find_returns_null_for_an_unknown_uid(): void
    {
        $this->putUpdate('2026-07-nieuwste', '2026-07-16');

        $this->assertNull(ProductUpdates::find('bestaat-niet'));
    }

    public function test_newer_and_older_walk_the_list_in_publication_order(): void
    {
        $this->putUpdate('2026-05-oudste', '2026-05-01');
        $this->putUpdate('2026-06-middelste', '2026-06-10');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16');

        $this->assertSame('2026-07-nieuwste', ProductUpdates::newerThan('2026-06-middelste')['uid']);
        $this->assertSame('2026-05-oudste', ProductUpdates::olderThan('2026-06-middelste')['uid']);
    }

    public function test_newer_is_null_at_the_top_and_older_is_null_at_the_bottom(): void
    {
        $this->putUpdate('2026-05-oudste', '2026-05-01');
        $this->putUpdate('2026-07-nieuwste', '2026-07-16');

        $this->assertNull(ProductUpdates::newerThan('2026-07-nieuwste'));
        $this->assertNull(ProductUpdates::olderThan('2026-05-oudste'));
    }

    public function test_neighbours_are_null_for_an_unknown_uid(): void
    {
        $this->putUpdate('2026-07-nieuwste', '2026-07-16');

        $this->assertNull(ProductUpdates::newerThan('bestaat-niet'));
        $this->assertNull(ProductUpdates::olderThan('bestaat-niet'));
    }

    public function test_render_content_turns_markdown_into_html(): void
    {
        $html = ProductUpdates::renderContent(['content' => "## Zo werkt het\n\nKlik op **Print**."]);

        $this->assertStringContainsString('<h2>Zo werkt het</h2>', $html);
        $this->assertStringContainsString('<strong>Print</strong>', $html);
    }

    public function test_render_content_strips_embedded_html(): void
    {
        $html = ProductUpdates::renderContent(['content' => 'Tekst <script>alert(1)</script> erna.']);

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_render_content_returns_null_when_there_is_no_content(): void
    {
        $this->assertNull(ProductUpdates::renderContent(['body' => 'Enkel een teaser.']));
        $this->assertNull(ProductUpdates::renderContent(['content' => '   ']));
    }
}
