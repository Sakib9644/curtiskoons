<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Biomarker;
use App\Models\BiomarkerRange;
use App\Models\BiomarkerGenetic;

class BiomarkerSeeder extends Seeder
{
   public function run(): void
{
    // Helper to create numeric biomarkers
    $addNumeric = function ($name, $label, $unit, $category, $ranges) {
        $bio = Biomarker::create([
            'name' => $name,
            'label' => $label,
            'unit' => $unit,
            'category' => $category,
            'is_numeric' => true,
        ]);

        foreach ($ranges as $r) {
            BiomarkerRange::create([
                'biomarker_id' => $bio->id,
                'range_start' => $r['range'][0],
                'range_end'   => $r['range'][1],
                'delta'       => $r['delta'],
            ]);
        }
    };

    // Helper to create genetic biomarkers
    $addGenetic = function ($name, $label, $category, $values) {
        $bio = Biomarker::create([
            'name' => $name,
            'label' => $label,
            'unit' => null,
            'category' => $category,
            'is_numeric' => false,
        ]);

        foreach ($values as $genotype => $delta) {
            BiomarkerGenetic::create([
                'biomarker_id' => $bio->id,
                'genotype'     => $genotype,
                'delta'        => $delta,
            ]);
        }
    };

    // =============================
    //  METABOLIC PANEL
    // =============================

    $addNumeric('fasting_glucose', 'Fasting Glucose', 'mg/dL', 'metabolic', [
        ['range' => [0, 69], 'delta' => 1],
        ['range' => [70, 85], 'delta' => -2],
        ['range' => [86, 99], 'delta' => 0],
        ['range' => [100, 125], 'delta' => 2],
        ['range' => [126, 1000], 'delta' => 4],
    ]);

    $addNumeric('hba1c', 'HbA1c', '%', 'metabolic', [
        ['range' => [0, 3.9], 'delta' => 2],
        ['range' => [4, 4.7], 'delta' => 0.5],
        ['range' => [4.8, 5.3], 'delta' => -2],
        ['range' => [5.4, 5.6], 'delta' => 0],
        ['range' => [5.7, 6.4], 'delta' => 2],
        ['range' => [6.5, 100], 'delta' => 4],
    ]);

    $addNumeric('fasting_insulin', 'Fasting Insulin', 'µU/mL', 'metabolic', [
        ['range' => [0, 1.9], 'delta' => 1],
        ['range' => [2, 6], 'delta' => -2],
        ['range' => [6.1, 10], 'delta' => 0],
        ['range' => [10.1, 15], 'delta' => 2],
        ['range' => [15.1, 100], 'delta' => 3.5],
    ]);

    $addNumeric('homa_ir', 'HOMA-IR', 'index', 'metabolic', [
        ['range' => [0, 0.49], 'delta' => 0.5],
        ['range' => [0.5, 1.4], 'delta' => -2],
        ['range' => [1.5, 2.5], 'delta' => 0.5],
        ['range' => [2.6, 4], 'delta' => 2.5],
        ['range' => [4.1, 100], 'delta' => 4],
    ]);

    // =============================
    //  LIVER FUNCTION PANEL
    // =============================

    $addNumeric('alt', 'ALT', 'U/L', 'liver', [
        ['range' => [0, 9], 'delta' => 1],
        ['range' => [10, 20], 'delta' => -1.5],
        ['range' => [21, 40], 'delta' => 0],
        ['range' => [41, 60], 'delta' => 1.5],
        ['range' => [61, 1000], 'delta' => 3],
    ]);

    $addNumeric('ast', 'AST', 'U/L', 'liver', [
        ['range' => [0, 9], 'delta' => 1],
        ['range' => [10, 20], 'delta' => -1.5],
        ['range' => [21, 39], 'delta' => 0],
        ['range' => [40, 60], 'delta' => 2.5],
        ['range' => [61, 1000], 'delta' => 4],
    ]);

    // Note: GGT algorithm specifies gender-specific ranges (>44 M, >35 F)
    // This implementation uses simplified male thresholds
    $addNumeric('ggt', 'GGT', 'U/L', 'liver', [
        ['range' => [0, 8], 'delta' => 0],
        ['range' => [9, 25], 'delta' => -1],
        ['range' => [26, 44], 'delta' => 1],
        ['range' => [45, 1000], 'delta' => 3],
    ]);

    // =============================
    //  KIDNEY FUNCTION PANEL
    // =============================

    $addNumeric('serum_creatinine', 'Serum Creatinine', 'mg/dL', 'kidney', [
        ['range' => [0, 0.59], 'delta' => 0.5],
        ['range' => [0.6, 0.9], 'delta' => -1.5],
        ['range' => [0.91, 1.1], 'delta' => 0],
        ['range' => [1.11, 1.3], 'delta' => 1.5],
        ['range' => [1.31, 100], 'delta' => 3],
    ]);

    $addNumeric('egfr', 'eGFR', 'mL/min/1.73m²', 'kidney', [
        ['range' => [0, 44], 'delta' => 4],
        ['range' => [45, 59], 'delta' => 2.5],
        ['range' => [60, 89], 'delta' => 1],
        ['range' => [90, 120], 'delta' => -1.5],
        ['range' => [121, 1000], 'delta' => -1],
    ]);

    // =============================
    //  INFLAMMATORY MARKERS
    // =============================

    $addNumeric('hs_crp', 'hs-CRP', 'mg/L', 'inflammation', [
        ['range' => [0, 0.49], 'delta' => -2],
        ['range' => [0.5, 1], 'delta' => -1],
        ['range' => [1.01, 3], 'delta' => 1],
        ['range' => [3.01, 10], 'delta' => 3],
        ['range' => [10.01, 100], 'delta' => 5],
    ]);

    $addNumeric('homocysteine', 'Homocysteine', 'µmol/L', 'inflammation', [
        ['range' => [0, 4.9], 'delta' => 0.5],
        ['range' => [5, 8], 'delta' => -2],
        ['range' => [8.1, 10], 'delta' => 0],
        ['range' => [10.1, 15], 'delta' => 2],
        ['range' => [15.1, 100], 'delta' => 4],
    ]);

    // =============================
    //  LIPID & CARDIOVASCULAR PANEL
    // =============================

    $addNumeric('triglycerides', 'Triglycerides', 'mg/dL', 'lipids', [
        ['range' => [0, 49], 'delta' => 0.5],
        ['range' => [50, 80], 'delta' => -1.5],
        ['range' => [81, 150], 'delta' => 0],
        ['range' => [151, 200], 'delta' => 1.5],
        ['range' => [201, 1000], 'delta' => 3],
    ]);

    $addNumeric('hdl_cholesterol', 'HDL Cholesterol', 'mg/dL', 'lipids', [
        ['range' => [0, 39], 'delta' => 2.5],
        ['range' => [40, 49], 'delta' => 1],
        ['range' => [50, 70], 'delta' => -1.5],
        ['range' => [71, 90], 'delta' => -1],
        ['range' => [91, 1000], 'delta' => 0],
    ]);

    $addNumeric('lpa', 'Lp(a)', 'nmol/L', 'lipids', [
        ['range' => [0, 29], 'delta' => -1],
        ['range' => [30, 75], 'delta' => 0],
        ['range' => [76, 125], 'delta' => 1.5],
        ['range' => [126, 1000], 'delta' => 3],
    ]);

    // =============================
    //  HEMATOLOGIC PANEL
    // =============================

    $addNumeric('rdw', 'RDW', '%', 'blood', [
        ['range' => [0, 11.4], 'delta' => 0.5],
        ['range' => [11.5, 13], 'delta' => -1],
        ['range' => [13.1, 14.5], 'delta' => 1],
        ['range' => [14.6, 100], 'delta' => 2.5],
    ]);

    $addNumeric('wbc_count', 'WBC Count', '10³/µL', 'blood', [
        ['range' => [0, 3.9], 'delta' => 1.5],
        ['range' => [4, 6], 'delta' => -1],
        ['range' => [6.1, 9], 'delta' => 0],
        ['range' => [9.1, 11], 'delta' => 1],
        ['range' => [11.1, 100], 'delta' => 2.5],
    ]);

    $addNumeric('lymphocyte_percentage', 'Lymphocyte %', '%', 'blood', [
        ['range' => [0, 19], 'delta' => 2],
        ['range' => [20, 24], 'delta' => 0.5],
        ['range' => [25, 35], 'delta' => -1],
        ['range' => [36, 40], 'delta' => 0],
        ['range' => [41, 100], 'delta' => 0.5],
    ]);

    $addNumeric('albumin', 'Albumin', 'g/dL', 'blood', [
        ['range' => [0, 3.39], 'delta' => 3],
        ['range' => [3.4, 3.9], 'delta' => 1.5],
        ['range' => [4, 4.8], 'delta' => -1.5],
        ['range' => [4.9, 5.4], 'delta' => 0],
        ['range' => [5.5, 100], 'delta' => 0.5],
    ]);

    // =============================
    //  GENETIC MARKERS
    // =============================

    $addGenetic('apoe', 'APOE Genotype', 'genetic', [
        'ε2/ε2' => -2,
        'ε2/ε3' => -1.5,
        'ε3/ε3' => 0,
        'ε3/ε4' => 2,
        'ε4/ε4' => 4,
    ]);

    $addGenetic('mthfr', 'MTHFR C677T', 'genetic', [
        'CC' => 0,
        'CT' => 0.5,
        'TT' => 1.5,
    ]);
}
}
