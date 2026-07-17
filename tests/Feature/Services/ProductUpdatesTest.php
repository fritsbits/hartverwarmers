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
}
