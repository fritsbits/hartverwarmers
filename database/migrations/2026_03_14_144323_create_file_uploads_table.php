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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->string('file_hash', 64)->comment('SHA-256 hash of uploaded file');
            $table->string('original_filename');
            $table->timestamp('disclaimer_accepted_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
