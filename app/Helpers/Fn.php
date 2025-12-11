<?php
use App\Models\CMS;
use App\Enums\PageEnum;
use App\Enums\SectionEnum;
use App\Models\Setting;
use App\Models\User;

function getFileName($file): string
{
    return time().'_'.pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
}




<?php

if (!function_exists('calculateChronologicalAge')) {
    function calculateChronologicalAge($birthDate): float {
        $birth = new DateTime($birthDate);
        $now = new DateTime();
        $interval = $birth->diff($now);

        // Calculate precise age with decimals (years + months/12 + days/365)
        $age = $interval->y + ($interval->m / 12) + ($interval->d / 365);

        return round($age, 1);
    }
}

if (!function_exists('calculateBlueAge')) {
    function calculateBlueAge(array $patientData): array {
        // Full list of biomarkers with their ranges and deltas
        $biomarkers = [
            'fasting_glucose' => [
                ['range'=>[0,69],'delta'=>1],
                ['range'=>[70,85],'delta'=>-2],
                ['range'=>[86,99],'delta'=>0],
                ['range'=>[100,125],'delta'=>2],
                ['range'=>[126,1000],'delta'=>4],
            ],
            'hba1c' => [
                ['range'=>[0,3.9],'delta'=>2],
                ['range'=>[4,4.7],'delta'=>0.5],
                ['range'=>[4.8,5.3],'delta'=>-2],
                ['range'=>[5.4,5.6],'delta'=>0],
                ['range'=>[5.7,6.4],'delta'=>2],
                ['range'=>[6.5,100],'delta'=>4],
            ],
            'fasting_insulin' => [
                ['range'=>[0,1.9],'delta'=>1],
                ['range'=>[2,6],'delta'=>-2],
                ['range'=>[6.1,10],'delta'=>0],
                ['range'=>[10.1,15],'delta'=>2],
                ['range'=>[15.1,100],'delta'=>3.5],
            ],
            'alt' => [
                ['range'=>[0,9],'delta'=>1],
                ['range'=>[10,20],'delta'=>-1.5],
                ['range'=>[21,40],'delta'=>0],
                ['range'=>[41,60],'delta'=>1.5],
                ['range'=>[61,1000],'delta'=>3],
            ],
            'ast' => [
                ['range'=>[0,9],'delta'=>1],
                ['range'=>[10,20],'delta'=>-1.5],
                ['range'=>[21,39],'delta'=>0],
                ['range'=>[40,60],'delta'=>2.5],
                ['range'=>[61,1000],'delta'=>4],
            ],
            'ggt' => [
                ['range'=>[0,8],'delta'=>0],
                ['range'=>[9,25],'delta'=>-1],
                ['range'=>[26,44],'delta'=>1],
                ['range'=>[45,1000],'delta'=>3],
            ],
            'serum_creatinine' => [
                ['range'=>[0,0.59],'delta'=>0.5],
                ['range'=>[0.6,0.9],'delta'=>-1.5],
                ['range'=>[0.91,1.1],'delta'=>0],
                ['range'=>[1.11,1.3],'delta'=>1.5],
                ['range'=>[1.31,100],'delta'=>3],
            ],
            'egfr' => [
                ['range'=>[0,44],'delta'=>4],
                ['range'=>[45,59],'delta'=>2.5],
                ['range'=>[60,89],'delta'=>1],
                ['range'=>[90,120],'delta'=>-1.5],
                ['range'=>[121,1000],'delta'=>-1],
            ],
            'hs_crp' => [
                ['range'=>[0,0.49],'delta'=>-2],
                ['range'=>[0.5,1],'delta'=>-1],
                ['range'=>[1.1,3],'delta'=>1],
                ['range'=>[3.1,10],'delta'=>3],
                ['range'=>[10.1,100],'delta'=>5],
            ],
            'homocysteine' => [
                ['range'=>[0,4.9],'delta'=>0.5],
                ['range'=>[5,8],'delta'=>-2],
                ['range'=>[8.1,10],'delta'=>0],
                ['range'=>[10.1,15],'delta'=>2],
                ['range'=>[15.1,100],'delta'=>4],
            ],
            'triglycerides' => [
                ['range'=>[0,49],'delta'=>0.5],
                ['range'=>[50,80],'delta'=>-1.5],
                ['range'=>[81,150],'delta'=>0],
                ['range'=>[151,200],'delta'=>1.5],
                ['range'=>[201,1000],'delta'=>3],
            ],
            'hdl_cholesterol' => [
                ['range'=>[0,39],'delta'=>2.5],
                ['range'=>[40,49],'delta'=>1],
                ['range'=>[50,70],'delta'=>-1.5],
                ['range'=>[71,90],'delta'=>-1],
                ['range'=>[91,1000],'delta'=>0],
            ],
            'lpa' => [
                ['range'=>[0,29],'delta'=>-1],
                ['range'=>[30,75],'delta'=>0],
                ['range'=>[76,125],'delta'=>1.5],
                ['range'=>[126,1000],'delta'=>3],
            ],
            'apoe' => [
                'ε2/ε2'=>-2,
                'ε2/ε3'=>-1.5,
                'ε3/ε3'=>0,
                'ε3/ε4'=>2,
                'ε4/ε4'=>4,
            ],
            'mthfr' => [
                'CC'=>0,
                'CT'=>0.5,
                'TT'=>1.5,
            ],
            'rdw' => [
                ['range'=>[0,11.4],'delta'=>0.5],
                ['range'=>[11.5,13],'delta'=>-1],
                ['range'=>[13.1,14.5],'delta'=>1],
                ['range'=>[14.6,100],'delta'=>2.5],
            ],
            'wbc_count' => [
                ['range'=>[0,3.9],'delta'=>1.5],
                ['range'=>[4,6],'delta'=>-1],
                ['range'=>[6.1,9],'delta'=>0],
                ['range'=>[9.1,11],'delta'=>1],
                ['range'=>[11.1,100],'delta'=>2.5],
            ],
            'lymphocyte_percentage' => [
                ['range'=>[0,19],'delta'=>2],
                ['range'=>[20,24],'delta'=>0.5],
                ['range'=>[25,35],'delta'=>-1],
                ['range'=>[36,40],'delta'=>0],
                ['range'=>[41,100],'delta'=>0.5],
            ],
            'albumin' => [
                ['range'=>[0,3.39],'delta'=>3],
                ['range'=>[3.4,3.9],'delta'=>1.5],
                ['range'=>[4,4.8],'delta'=>-1.5],
                ['range'=>[4.9,5.4],'delta'=>0],
                ['range'=>[5.5,100],'delta'=>0.5],
            ],
        ];

        // Helper function for numeric biomarkers
        $getDelta = function($value, $ranges) {
            foreach($ranges as $r){
                if(isset($r['range']) && is_array($r['range']) && count($r['range']) === 2){
                    if($value >= $r['range'][0] && $value <= $r['range'][1]){
                        return $r['delta'];
                    }
                }
            }
            return 0;
        };

        $chronologicalAge = $patientData['chronological_age'] ?? 0;
        $coreLabAge = $chronologicalAge; // Start with chronological age

        // Track individual deltas for debugging
        $deltaBreakdown = [];

        foreach($biomarkers as $key => $ranges){
            if(!is_array($ranges) || count($ranges) === 0){
                continue;
            }

            $firstElement = reset($ranges);

            if(is_array($firstElement) && isset($firstElement['range'])){
                // Numeric biomarkers
                if(isset($patientData[$key]) && is_numeric($patientData[$key])){
                    $delta = $getDelta($patientData[$key], $ranges);
                    $coreLabAge += $delta;
                    $deltaBreakdown[$key] = $delta;
                }
            } else {
                // Genetic biomarkers (key-value pairs)
                if(isset($patientData[$key]) && isset($ranges[$patientData[$key]])){
                    $delta = $ranges[$patientData[$key]];
                    $coreLabAge += $delta;
                    $deltaBreakdown[$key] = $delta;
                }
            }
        }

        // Calculate optimal range
        $minDelta = 0;
        $maxDelta = 0;

        foreach($biomarkers as $key => $ranges){
            if(!is_array($ranges) || count($ranges) === 0){
                continue;
            }

            $firstElement = reset($ranges);

            if(is_array($firstElement) && isset($firstElement['delta'])){
                // Numeric biomarkers
                $deltas = array_column($ranges, 'delta');
                $minDelta += min($deltas);
                $maxDelta += max($deltas);
            } else {
                // Genetic biomarkers
                $vals = array_values($ranges);
                $minDelta += min($vals);
                $maxDelta += max($vals);
            }
        }

        $optimalRange = round($chronologicalAge + $minDelta, 1)
                      . '–'
                      . round($chronologicalAge + $maxDelta, 1)
                      . ' years';

        // Calculate fitness adjustment
        $expectedVO2 = 60 - (0.5 * $chronologicalAge);
        $expectedHRV = 100 - (0.8 * $chronologicalAge);

        $fitnessAdj = 0;
        if(isset($patientData['vo2max']) && is_numeric($patientData['vo2max']) && $patientData['vo2max'] < $expectedVO2){
            $fitnessAdj = ($expectedVO2 - $patientData['vo2max']) * 0.1;
        }

        // Lifestyle adjustment
        $lifestyleAdj = $patientData['lifestyle_delta'] ?? 0;

        // Calculate final Blue Age
        $finalBlueAge = $coreLabAge + $fitnessAdj + $lifestyleAdj;

        return [
            'blue_age' => round($finalBlueAge, 1), // ← ADDED THIS!
            'core_lab_age' => round($coreLabAge, 1),
            'chronological_age' => $chronologicalAge,
            'delta_age' => round($coreLabAge - $chronologicalAge, 1),
            'fitness_adj' => round($fitnessAdj, 1),
            'lifestyle_adj' => round($lifestyleAdj, 1),
            'optimal_range' => $optimalRange,
            'expected_vo2max' => round($expectedVO2, 1),
            'expected_hrv' => round($expectedHRV, 1),
            'last_updated' => date('F d, Y'),
            'delta_breakdown' => $deltaBreakdown,
        ];
    }
}

if (!function_exists('calculateBlueAge')) {
    function calculateBlueAge(array $patientData): array {
        // Full list of biomarkers with their ranges and deltas
        $biomarkers = [
            'fasting_glucose' => [
                ['range'=>[0,69],'delta'=>1],
                ['range'=>[70,85],'delta'=>-2],
                ['range'=>[86,99],'delta'=>0],
                ['range'=>[100,125],'delta'=>2],
                ['range'=>[126,1000],'delta'=>4],
            ],
            'hba1c' => [
                ['range'=>[0,3.9],'delta'=>2],
                ['range'=>[4,4.7],'delta'=>0.5],
                ['range'=>[4.8,5.3],'delta'=>-2],
                ['range'=>[5.4,5.6],'delta'=>0],
                ['range'=>[5.7,6.4],'delta'=>2],
                ['range'=>[6.5,100],'delta'=>4],
            ],
            'fasting_insulin' => [
                ['range'=>[0,1.9],'delta'=>1],
                ['range'=>[2,6],'delta'=>-2],
                ['range'=>[6.1,10],'delta'=>0],
                ['range'=>[10.1,15],'delta'=>2],
                ['range'=>[15.1,100],'delta'=>3.5],
            ],
            'alt' => [
                ['range'=>[0,9],'delta'=>1],
                ['range'=>[10,20],'delta'=>-1.5],
                ['range'=>[21,40],'delta'=>0],
                ['range'=>[41,60],'delta'=>1.5],
                ['range'=>[61,1000],'delta'=>3],
            ],
            'ast' => [
                ['range'=>[0,9],'delta'=>1],
                ['range'=>[10,20],'delta'=>-1.5],
                ['range'=>[21,39],'delta'=>0],
                ['range'=>[40,60],'delta'=>2.5],
                ['range'=>[61,1000],'delta'=>4],
            ],
            'ggt' => [
                ['range'=>[0,8],'delta'=>0],
                ['range'=>[9,25],'delta'=>-1],
                ['range'=>[26,44],'delta'=>1],
                ['range'=>[45,1000],'delta'=>3],
            ],
            'serum_creatinine' => [
                ['range'=>[0,0.59],'delta'=>0.5],
                ['range'=>[0.6,0.9],'delta'=>-1.5],
                ['range'=>[0.91,1.1],'delta'=>0],
                ['range'=>[1.11,1.3],'delta'=>1.5],
                ['range'=>[1.31,100],'delta'=>3],
            ],
            'egfr' => [
                ['range'=>[0,44],'delta'=>4],
                ['range'=>[45,59],'delta'=>2.5],
                ['range'=>[60,89],'delta'=>1],
                ['range'=>[90,120],'delta'=>-1.5],
                ['range'=>[121,1000],'delta'=>-1],
            ],
            'hs_crp' => [
                ['range'=>[0,0.49],'delta'=>-2],
                ['range'=>[0.5,1],'delta'=>-1],
                ['range'=>[1.1,3],'delta'=>1],
                ['range'=>[3.1,10],'delta'=>3],
                ['range'=>[10.1,100],'delta'=>5],
            ],
            'homocysteine' => [
                ['range'=>[0,4.9],'delta'=>0.5],
                ['range'=>[5,8],'delta'=>-2],
                ['range'=>[8.1,10],'delta'=>0],
                ['range'=>[10.1,15],'delta'=>2],
                ['range'=>[15.1,100],'delta'=>4],
            ],
            'triglycerides' => [
                ['range'=>[0,49],'delta'=>0.5],
                ['range'=>[50,80],'delta'=>-1.5],
                ['range'=>[81,150],'delta'=>0],
                ['range'=>[151,200],'delta'=>1.5],
                ['range'=>[201,1000],'delta'=>3],
            ],
            'hdl_cholesterol' => [
                ['range'=>[0,39],'delta'=>2.5],
                ['range'=>[40,49],'delta'=>1],
                ['range'=>[50,70],'delta'=>-1.5],
                ['range'=>[71,90],'delta'=>-1],
                ['range'=>[91,1000],'delta'=>0],
            ],
            'lpa' => [
                ['range'=>[0,29],'delta'=>-1],
                ['range'=>[30,75],'delta'=>0],
                ['range'=>[76,125],'delta'=>1.5],
                ['range'=>[126,1000],'delta'=>3],
            ],
            'apoe' => [
                'ε2/ε2'=>-2,
                'ε2/ε3'=>-1.5,
                'ε3/ε3'=>0,
                'ε3/ε4'=>2,
                'ε4/ε4'=>4,
            ],
            'mthfr' => [
                'CC'=>0,
                'CT'=>0.5,
                'TT'=>1.5,
            ],
            'rdw' => [
                ['range'=>[0,11.4],'delta'=>0.5],
                ['range'=>[11.5,13],'delta'=>-1],
                ['range'=>[13.1,14.5],'delta'=>1],
                ['range'=>[14.6,100],'delta'=>2.5],
            ],
            'wbc_count' => [
                ['range'=>[0,3.9],'delta'=>1.5],
                ['range'=>[4,6],'delta'=>-1],
                ['range'=>[6.1,9],'delta'=>0],
                ['range'=>[9.1,11],'delta'=>1],
                ['range'=>[11.1,100],'delta'=>2.5],
            ],
            'lymphocyte_percentage' => [
                ['range'=>[0,19],'delta'=>2],
                ['range'=>[20,24],'delta'=>0.5],
                ['range'=>[25,35],'delta'=>-1],
                ['range'=>[36,40],'delta'=>0],
                ['range'=>[41,100],'delta'=>0.5],
            ],
            'albumin' => [
                ['range'=>[0,3.39],'delta'=>3],
                ['range'=>[3.4,3.9],'delta'=>1.5],
                ['range'=>[4,4.8],'delta'=>-1.5],
                ['range'=>[4.9,5.4],'delta'=>0],
                ['range'=>[5.5,100],'delta'=>0.5],
            ],
        ];

        // Helper function for numeric biomarkers
        $getDelta = function($value, $ranges) {
            foreach($ranges as $r){
                if(isset($r['range']) && is_array($r['range']) && count($r['range']) === 2){
                    if($value >= $r['range'][0] && $value <= $r['range'][1]){
                        return $r['delta'];
                    }
                }
            }
            return 0;
        };

        $chronologicalAge = $patientData['chronological_age'] ?? 0;
        $coreLabAge = $chronologicalAge; // Start with chronological age

        // Track individual deltas for debugging
        $deltaBreakdown = [];

        foreach($biomarkers as $key => $ranges){
            if(!is_array($ranges) || count($ranges) === 0){
                continue;
            }

            $firstElement = reset($ranges);

            if(is_array($firstElement) && isset($firstElement['range'])){
                // Numeric biomarkers
                if(isset($patientData[$key]) && is_numeric($patientData[$key])){
                    $delta = $getDelta($patientData[$key], $ranges);
                    $coreLabAge += $delta;
                    $deltaBreakdown[$key] = $delta;
                }
            } else {
                // Genetic biomarkers (key-value pairs)
                if(isset($patientData[$key]) && isset($ranges[$patientData[$key]])){
                    $delta = $ranges[$patientData[$key]];
                    $coreLabAge += $delta;
                    $deltaBreakdown[$key] = $delta;
                }
            }
        }

        // Calculate optimal range
        $minDelta = 0;
        $maxDelta = 0;

        foreach($biomarkers as $key => $ranges){
            if(!is_array($ranges) || count($ranges) === 0){
                continue;
            }

            $firstElement = reset($ranges);

            if(is_array($firstElement) && isset($firstElement['delta'])){
                // Numeric biomarkers
                $deltas = array_column($ranges, 'delta');
                $minDelta += min($deltas);
                $maxDelta += max($deltas);
            } else {
                // Genetic biomarkers
                $vals = array_values($ranges);
                $minDelta += min($vals);
                $maxDelta += max($vals);
            }
        }

        $optimalRange = round($chronologicalAge + $minDelta, 1)
                      . '–'
                      . round($chronologicalAge + $maxDelta, 1)
                      . ' years';

        return [
            'core_lab_age' => round($coreLabAge, 1), // This is chronological + biomarker deltas
            'chronological_age' => $chronologicalAge,
            'optimal_range' => $optimalRange,
            'last_updated' => date('F d, Y'),
            'delta_breakdown' => $deltaBreakdown, // For debugging
        ];
    }
}
function getEmailName($email): string
{
    $parts = explode('@', $email);
    return $parts[0];
}
function getCommonData()
{
    $common = CMS::where('page', PageEnum::COMMON)->where('status', 'active');
    foreach (SectionEnum::getCommon() as $key => $section) {
        $cms[$key] = (clone $common)->where('section', $key)->latest()->take($section['item'])->{$section['type']}();
    }
    return $cms;
}

function formatNumber($number, $precision = 2): array
{
    if ($number >= 1000000000000000) {
        return [
            'number' => number_format($number / 1000000000000000, $precision),
            'format' => 'Q'
        ];
    } elseif ($number >= 1000000000000) {
        return [
            'number' => number_format($number / 1000000000000, $precision),
            'format' => 'T'
        ];
    } elseif ($number >= 1000000000) {
        return [
            'number' => number_format($number / 1000000000, $precision),
            'format' => 'B'
        ];
    } elseif ($number >= 1000000) {
        return [
            'number' => number_format($number / 1000000, $precision),
            'format' => 'M'
        ];
    } elseif ($number >= 1000) {
        return [
            'number' => number_format($number / 1000, $precision),
            'format' => 'K'
        ];
    }

    // For numbers less than 1K, no format suffix is needed
    return [
        'number' => number_format($number),
        'format' => ''
    ];
}

if (!function_exists('is_url')) {
    function is_url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('settings')) {
    function settings(?string $key = null)
    {
        $settings = Setting::first();
        if ($key) {
            return $settings->{$key} ?? null;
        }
        return $settings;
    }
}





