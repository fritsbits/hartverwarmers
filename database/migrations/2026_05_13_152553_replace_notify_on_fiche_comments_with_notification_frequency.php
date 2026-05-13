<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('notification_frequency')->default('daily')->after('notify_on_onboarding_emails');
        });

        DB::table('users')
            ->where('notify_on_fiche_comments', false)
            ->update(['notification_frequency' => 'never']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notify_on_fiche_comments');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_on_fiche_comments')->default(true)->after('notify_on_onboarding_emails');
        });

        DB::table('users')
            ->where('notification_frequency', 'never')
            ->update(['notify_on_fiche_comments' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_frequency');
        });
    }
};
