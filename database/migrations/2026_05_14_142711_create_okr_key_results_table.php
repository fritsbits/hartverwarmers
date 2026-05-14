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
        Schema::create('okr_key_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')
                ->constrained('okr_objectives')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('metric_key')->nullable();
            $table->integer('target_value')->nullable();
            $table->string('target_unit')->default('');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_key_results');
    }
};
