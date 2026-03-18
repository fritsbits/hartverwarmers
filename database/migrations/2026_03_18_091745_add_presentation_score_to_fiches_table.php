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
            $table->unsignedTinyInteger('presentation_score')->nullable()->after('completeness_score');
            $table->text('presentation_justification')->nullable()->after('presentation_score');
        });
    }

    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropColumn(['presentation_score', 'presentation_justification']);
        });
    }
};
