<?php

namespace Tests\Feature\Models;

use App\Models\ProductUpdateSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_seen_product_update_is_false_without_a_send_row(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasSeenProductUpdate('2026-07-themakalender-afdrukken'));
    }

    public function test_has_seen_product_update_is_true_after_a_send_is_recorded(): void
    {
        $user = User::factory()->create();

        ProductUpdateSend::create([
            'user_id' => $user->id,
            'update_uid' => '2026-07-themakalender-afdrukken',
            'sent_at' => now(),
        ]);

        $this->assertTrue($user->hasSeenProductUpdate('2026-07-themakalender-afdrukken'));
        $this->assertFalse($user->hasSeenProductUpdate('2026-08-iets-anders'));
    }

    public function test_send_rows_are_deleted_with_the_user(): void
    {
        $user = User::factory()->create();

        ProductUpdateSend::create([
            'user_id' => $user->id,
            'update_uid' => '2026-07-themakalender-afdrukken',
            'sent_at' => now(),
        ]);

        $user->forceDelete();

        $this->assertDatabaseCount('product_update_sends', 0);
    }
}
