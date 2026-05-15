<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_initiative_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiative_id')->constrained('okr_initiatives')->cascadeOnDelete();
            $table->foreignId('key_result_id')->constrained('okr_key_results')->cascadeOnDelete();
            $table->decimal('baseline_value', 10, 2)->nullable();
            $table->string('baseline_unit')->default('');
            $table->timestamp('baseline_at');
            $table->boolean('low_data')->default(false);
            $table->timestamps();

            $table->unique(['initiative_id', 'key_result_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_initiative_baselines');
    }
};
