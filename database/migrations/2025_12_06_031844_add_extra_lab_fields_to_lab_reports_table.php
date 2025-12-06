<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_reports', function (Blueprint $table) {
            $table->id();

            // Patient Information
            $table->string('patient_name')->nullable();
            $table->string('patient_address')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_id')->nullable();
            $table->string('alternate_patient_id')->nullable();
            $table->date('patient_dob')->nullable();
            $table->integer('age')->nullable();
            $table->string('patient_gender')->nullable();

            // Physician Information
            $table->string('ordering_physician')->nullable();
            $table->string('physician_id')->nullable();
            $table->string('physician_npi')->nullable();
            $table->string('lab_name')->nullable();
            $table->string('lab_address')->nullable();
            $table->string('lab_phone')->nullable();
            $table->text('lab_notes')->nullable();

            // Specimen Information
            $table->string('account_number')->nullable();
            $table->string('specimen_id')->nullable();
            $table->string('control_id')->nullable();
            $table->string('alternate_control_number')->nullable();
            $table->dateTime('collection_date')->nullable();
            $table->dateTime('date_received')->nullable();
            $table->dateTime('date_entered')->nullable();
            $table->dateTime('report_date')->nullable();

            // Report metadata
            $table->text('disclaimer')->nullable();
            $table->text('performing_labs')->nullable();
            $table->text('icon_legend')->nullable();

            // PSA
            $table->string('psa_current')->nullable();
            $table->string('psa_previous')->nullable();
            $table->date('psa_previous_date')->nullable();
            $table->string('psa_units')->nullable();
            $table->string('psa_reference')->nullable();

            // IGF-1
            $table->string('igf1_current')->nullable();
            $table->string('igf1_previous')->nullable();
            $table->date('igf1_previous_date')->nullable();
            $table->string('igf1_units')->nullable();
            $table->string('igf1_reference')->nullable();

            // Vitamin D
            $table->string('vitamin_d_current')->nullable();
            $table->string('vitamin_d_previous')->nullable();
            $table->date('vitamin_d_previous_date')->nullable();
            $table->string('vitamin_d_units')->nullable();
            $table->string('vitamin_d_reference')->nullable();

            // C-Reactive Protein
            $table->string('crp_current')->nullable();
            $table->string('crp_previous')->nullable();
            $table->date('crp_previous_date')->nullable();
            $table->string('crp_units')->nullable();
            $table->string('crp_reference')->nullable();

            // TMAO
            $table->string('tmao_current')->nullable();
            $table->string('tmao_previous')->nullable();
            $table->date('tmao_previous_date')->nullable();
            $table->string('tmao_units')->nullable();
            $table->string('tmao_reference')->nullable();

            // Homocysteine
            $table->string('homocysteine_current')->nullable();
            $table->string('homocysteine_previous')->nullable();
            $table->date('homocysteine_previous_date')->nullable();
            $table->string('homocysteine_units')->nullable();
            $table->string('homocysteine_reference')->nullable();

            // Uric Acid
            $table->string('uric_acid_current')->nullable();
            $table->string('uric_acid_previous')->nullable();
            $table->date('uric_acid_previous_date')->nullable();
            $table->string('uric_acid_units')->nullable();
            $table->string('uric_acid_reference')->nullable();

            // Vitamin B12
            $table->string('vitamin_b12_current')->nullable();
            $table->string('vitamin_b12_previous')->nullable();
            $table->date('vitamin_b12_previous_date')->nullable();
            $table->string('vitamin_b12_units')->nullable();
            $table->string('vitamin_b12_reference')->nullable();

            // Insulin
            $table->string('insulin_current')->nullable();
            $table->string('insulin_previous')->nullable();
            $table->date('insulin_previous_date')->nullable();
            $table->string('insulin_units')->nullable();
            $table->string('insulin_reference')->nullable();

            // Ferritin
            $table->string('ferritin_current')->nullable();
            $table->string('ferritin_previous')->nullable();
            $table->date('ferritin_previous_date')->nullable();
            $table->string('ferritin_units')->nullable();
            $table->string('ferritin_reference')->nullable();

            // Free T3
            $table->string('t3_free_current')->nullable();
            $table->string('t3_free_previous')->nullable();
            $table->date('t3_free_previous_date')->nullable();
            $table->string('t3_free_units')->nullable();
            $table->string('t3_free_reference')->nullable();

            // SHBG
            $table->string('shbg_current')->nullable();
            $table->string('shbg_previous')->nullable();
            $table->date('shbg_previous_date')->nullable();
            $table->string('shbg_units')->nullable();
            $table->string('shbg_reference')->nullable();

            // File / User info
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->nullable();
            $table->text('report_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_reports');
    }
};
