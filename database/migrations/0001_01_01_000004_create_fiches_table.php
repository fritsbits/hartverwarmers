<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiative_id')->nullable()->constrained('initiatives')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('practical_tips')->nullable();
            $table->json('materials')->nullable();
            $table->json('target_audience')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('has_diamond')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('kudos_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiches');
    }
};
