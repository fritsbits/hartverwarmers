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
        Schema::create('okr_initiatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')
                ->constrained('okr_objectives')
                ->cascadeOnDelete();
            $table->string('slug');
            $table->string('label');
            $table->string('status')->default('in_progress');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['objective_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_initiatives');
    }
};
