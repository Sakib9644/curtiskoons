<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_goals', function (Blueprint $table) {
            $table->id();
            $table->string('goal');                 // e.g., "Reduce ApoB toward 70 mg/dL"
            $table->text('methods')->nullable();    // e.g., "Diet quality, omega-3, meds if indicated"
            $table->float('timeline_years')->nullable(); // e.g., 1.5
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_goals');
    }
};
