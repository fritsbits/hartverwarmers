<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_bounces', function (Blueprint $table): void {
            $table->text('reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_bounces', function (Blueprint $table): void {
            $table->string('reason')->nullable()->change();
        });
    }
};
