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
            $table->unsignedTinyInteger('quality_score')->nullable()->after('featured_month');
            $table->text('quality_justification')->nullable()->after('quality_score');
            $table->timestamp('quality_assessed_at')->nullable()->after('quality_justification');
            $table->unsignedTinyInteger('completeness_score')->nullable()->after('quality_assessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropColumn(['quality_score', 'quality_justification', 'quality_assessed_at', 'completeness_score']);
        });
    }
};
