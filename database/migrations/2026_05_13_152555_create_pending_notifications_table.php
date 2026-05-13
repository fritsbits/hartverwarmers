<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->foreignId('fiche_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'fiche_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_notifications');
    }
};
