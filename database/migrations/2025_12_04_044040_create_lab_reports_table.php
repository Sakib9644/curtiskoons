<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lab_reports', function (Blueprint $table) {
            $table->id();

            // Link to the user
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Uploaded file path
            $table->string('file_path');

            // Spike API record ID
            $table->uuid('record_id')->nullable();

            // Status from Spike API (completed, failed, etc.)
            $table->string('status')->default('pending');

            // Dates
            $table->date('collection_date')->nullable();
            $table->date('result_date')->nullable();

            // Patient info
            $table->string('patient_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->date('patient_dob')->nullable();
            $table->string('patient_gender')->nullable();

            // Lab info
            $table->string('lab_name')->nullable();
            $table->string('lab_address')->nullable();
            $table->string('lab_phone')->nullable();
            $table->string('lab_notes')->nullable();

            // Additional notes from report
            $table->text('report_notes')->nullable();

            // Dynamic results JSON (all sections & tests)
            $table->json('sections')->nullable();

            // Default timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_reports');
    }
};
