<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reassign fiches from "Hartverwarmers Import" (ID 10) to Frederik Vincx (ID 1)
        DB::table('fiches')->where('user_id', 10)->update(['user_id' => 1]);
    }
};
