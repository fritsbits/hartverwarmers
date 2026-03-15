<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->unsignedBigInteger('migration_id')->nullable()->after('icon');
            $table->index('migration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropIndex(['migration_id']);
            $table->dropColumn('migration_id');
        });
    }
};
