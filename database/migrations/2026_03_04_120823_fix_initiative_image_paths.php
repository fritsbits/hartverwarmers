<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-run initiative image path update for remote DB where the original
     * migration ran before seeded data existed.
     */
    public function up(): void
    {
        DB::table('initiatives')
            ->where('image', 'like', '/storage/initiatives/%')
            ->update([
                'image' => DB::raw("REPLACE(image, '/storage/initiatives/', '/img/initiatives/')"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('initiatives')
            ->where('image', 'like', '/img/initiatives/%')
            ->update([
                'image' => DB::raw("REPLACE(image, '/img/initiatives/', '/storage/initiatives/')"),
            ]);
    }
};
