<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
        });

        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('interest');
            $table->unsignedBigInteger('interest_category_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('domain_id')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('airtable_id', 100)->nullable();

            $table->foreign('interest_category_id')->references('id')->on('interest_categories')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('interests')->nullOnDelete();
        });

        Schema::create('activity_interest', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('interest_id');

            $table->primary(['activity_id', 'interest_id']);
            $table->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
            $table->foreign('interest_id')->references('id')->on('interests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_interest');
        Schema::dropIfExists('interests');
        Schema::dropIfExists('interest_categories');
    }
};
