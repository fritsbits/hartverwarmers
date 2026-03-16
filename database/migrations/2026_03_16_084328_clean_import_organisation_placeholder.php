<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('organisation', 'Import')
            ->update(['organisation' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible — placeholder data was meaningless
    }
};
