<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('last_digest_diamond_fiche_id')->nullable()->constrained('fiches')->nullOnDelete();
        });

        // Backfill with the current diamond: recent digests already showed it,
        // so without this the no-repeat guard has no baseline for existing users.
        $latestDiamondId = DB::table('fiches')
            ->where('published', true)
            ->where('has_diamond', true)
            ->whereNull('deleted_at')
            ->orderByDesc('diamond_awarded_at')
            ->orderByDesc('created_at')
            ->value('id');

        if ($latestDiamondId) {
            DB::table('users')->update(['last_digest_diamond_fiche_id' => $latestDiamondId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_digest_diamond_fiche_id');
        });
    }
};
