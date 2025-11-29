<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genetic_risk_factors', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., Cardiovascular Health
            $table->text('description')->nullable(); // details
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genetic_risk_factors');
    }
};
