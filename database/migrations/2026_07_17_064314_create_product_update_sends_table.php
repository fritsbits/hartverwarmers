<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_update_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('update_uid');
            $table->timestamp('sent_at');
            $table->unique(['user_id', 'update_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_update_sends');
    }
};
