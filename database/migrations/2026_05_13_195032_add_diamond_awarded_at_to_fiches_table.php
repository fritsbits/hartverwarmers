<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->timestamp('diamond_awarded_at')->nullable()->after('has_diamond');
        });

        DB::table('fiches')
            ->where('has_diamond', true)
            ->update(['diamond_awarded_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropColumn('diamond_awarded_at');
        });
    }
};
