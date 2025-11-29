<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twelve_week_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Plan title
            $table->text('description')->nullable(); // Plan description
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twelve_week_plans');
    }
};
