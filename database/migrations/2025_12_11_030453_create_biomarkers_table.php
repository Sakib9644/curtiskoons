<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biomarkers', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();       // e.g., fasting_glucose, hba1c
            $table->string('label')->nullable();    // Display name for admin/UI
            $table->string('unit')->nullable();     // mg/dL, %, U/L, etc.

            $table->string('category')->nullable(); // metabolic, liver, kidney, blood, genetic
            $table->boolean('is_numeric')->default(true); // false = genetic (APOE, MTHFR)

            $table->boolean('active')->default(true); // admin can enable/disable biomarkers

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biomarkers');
    }
};
