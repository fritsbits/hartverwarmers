<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiche_theme', function (Blueprint $table) {
            $table->foreignId('fiche_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['fiche_id', 'theme_id']);
            $table->index('theme_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiche_theme');
    }
};
