<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('initiatives', function (Blueprint $table) {
            $table->json('diamant_guidance')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('initiatives', function (Blueprint $table) {
            $table->dropColumn('diamant_guidance');
        });
    }
};
