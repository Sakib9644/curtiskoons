<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biomarker_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biomarker_id')->constrained('biomarkers')->onDelete('cascade');

            $table->decimal('range_start', 10, 2);
            $table->decimal('range_end', 10, 2);

            $table->decimal('delta', 10, 2); // positive or negative

            $table->integer('order')->nullable(); // admin sorting

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biomarker_ranges');
    }
};
