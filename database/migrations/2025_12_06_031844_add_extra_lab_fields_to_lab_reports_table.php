<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_reports', function (Blueprint $table) {

            $table->date('date_of_birth')->nullable();
            $table->date('test_date')->nullable();
            $table->string('chronological_age')->nullable();
            $table->string('total_delta')->nullable();
            $table->string('blue_age')->nullable();
            $table->text('interpretation')->nullable();

            // Metabolic Panel
            $table->float('fasting_glucose')->nullable();
            $table->float('hba1c')->nullable();
            $table->float('fasting_insulin')->nullable();
            $table->float('homa_ir')->nullable();

            // Liver Function
            $table->float('alt')->nullable();
            $table->float('ast')->nullable();
            $table->float('ggt')->nullable();

            // Kidney Function
            $table->float('serum_creatinine')->nullable();
            $table->float('egfr')->nullable();

            // Inflammation Markers
            $table->float('hs_crp')->nullable();
            $table->float('homocysteine')->nullable();

            // Lipid Panel
            $table->float('triglycerides')->nullable();
            $table->float('hdl_cholesterol')->nullable();
            $table->float('lp_a')->nullable();

            // Hematologic Panel
            $table->float('wbc_count')->nullable();
            $table->float('lymphocyte_percentage')->nullable();
            $table->float('rdw')->nullable();
            $table->float('albumin')->nullable();

            // Genetic Markers
            $table->string('apoe_genotype')->nullable();
            $table->string('mthfr_c677t')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lab_reports', function (Blueprint $table) {
            $table->dropColumn([
                'patient_name',
                'date_of_birth',
                'test_date',
                'chronological_age',
                'total_delta',
                'blue_age',
                'interpretation',

                // Metabolic Panel
                'fasting_glucose',
                'hba1c',
                'fasting_insulin',
                'homa_ir',

                // Liver Function
                'alt',
                'ast',
                'ggt',

                // Kidney Function
                'serum_creatinine',
                'egfr',

                // Inflammation Markers
                'hs_crp',
                'homocysteine',

                // Lipid Panel
                'triglycerides',
                'hdl_cholesterol',
                'lp_a',

                // Hematologic Panel
                'wbc_count',
                'lymphocyte_percentage',
                'rdw',
                'albumin',

                // Genetic Markers
                'apoe_genotype',
                'mthfr_c677t'
            ]);
        });
    }
};
