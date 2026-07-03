<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diamond_rotations', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique();
            $table->foreignId('fiche_id')->nullable()->constrained()->nullOnDelete();
            $table->json('suggested_fiche_ids')->nullable();
            $table->string('chosen_via')->default('auto');
            $table->timestamp('suggestion_sent_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diamond_rotations');
    }
};
