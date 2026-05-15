<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('okr_initiatives', function (Blueprint $table) {
            $table->date('started_at')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('okr_initiatives', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
};
