<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('guidances')->nullable();
            $table->json('fiche')->nullable();
            $table->json('target_audience')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('shared')->default(false);
            $table->unsignedInteger('carehome_id')->nullable();
            $table->unsignedInteger('template_id')->nullable();
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->text('quality_notes')->nullable();
            $table->unsignedTinyInteger('completeness_percentage')->nullable();
            $table->unsignedInteger('origin_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
