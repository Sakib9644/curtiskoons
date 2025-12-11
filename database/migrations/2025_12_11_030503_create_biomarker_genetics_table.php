<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biomarker_genetics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biomarker_id')->constrained('biomarkers')->onDelete('cascade');

            $table->string('genotype');  // e2/e3, CC, TT, etc.
            $table->decimal('delta', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biomarker_genetics');
    }
};
