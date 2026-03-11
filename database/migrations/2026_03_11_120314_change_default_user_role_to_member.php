<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: contributors without fiches become members
        DB::table('users')
            ->where('role', 'contributor')
            ->whereNotIn('id', DB::table('fiches')->distinct()->select('user_id'))
            ->update(['role' => 'member']);

        // Change the column default
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('contributor')->change();
        });

        DB::table('users')
            ->where('role', 'member')
            ->update(['role' => 'contributor']);
    }
};
