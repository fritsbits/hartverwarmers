<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->index('published');
        });

        Schema::table('initiatives', function (Blueprint $table) {
            $table->index('published');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropIndex(['published']);
        });

        Schema::table('initiatives', function (Blueprint $table) {
            $table->dropIndex(['published']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
