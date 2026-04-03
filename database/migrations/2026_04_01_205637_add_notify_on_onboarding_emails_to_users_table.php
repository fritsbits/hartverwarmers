<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_on_onboarding_emails')->default(true);
        });

        // Opt out existing users — only newly registered users should receive onboarding emails
        DB::table('users')->whereNotNull('email_verified_at')->update(['notify_on_onboarding_emails' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notify_on_onboarding_emails');
        });
    }
};
