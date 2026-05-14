<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('notification_frequency', 'daily')
            ->update(['notification_frequency' => 'weekly']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('notification_frequency')->default('weekly')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('notification_frequency')->default('daily')->change();
        });
    }
};
