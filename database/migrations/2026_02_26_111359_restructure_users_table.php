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
            $table->string('first_name')->after('id')->default('');
            $table->string('last_name')->after('first_name')->default('');
            $table->string('organisation')->nullable()->after('function_title');
            $table->string('website')->nullable()->after('bio');
            $table->string('linkedin')->nullable()->after('website');
        });

        // Migrate existing name data using PHP for cross-DB compatibility
        foreach (DB::table('users')->select('id', 'name', 'organisation_id')->get() as $user) {
            $parts = explode(' ', $user->name ?? '', 2);
            $orgName = $user->organisation_id
                ? DB::table('organisations')->where('id', $user->organisation_id)->value('name')
                : null;

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? '',
                'organisation' => $orgName,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organisation_id']);
            $table->dropColumn(['name', 'organisation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
            $table->foreignId('organisation_id')->nullable()->after('function_title')->constrained('organisations')->nullOnDelete();
        });

        foreach (DB::table('users')->select('id', 'first_name', 'last_name')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => trim("{$user->first_name} {$user->last_name}"),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'organisation', 'website', 'linkedin']);
        });
    }
};
