<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\LabReport;
use Illuminate\Support\Facades\Log;

class LabReportController extends Controller
{
    public function store(Request $request, $userId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileContents = base64_encode(file_get_contents($file->getPathname()));

        $appId = env('SPIKE_APPLICATION_ID');
        $hmacKey = env('SPIKE_HMAC_KEY');
        $baseUrl = env('SPIKE_API_BASE_URL', 'https://app-api.spikeapi.com/v3');

        try {
            // 1️⃣ Generate HMAC signature
            $signature = hash_hmac('sha256', (string)$userId, $hmacKey);

            // 2️⃣ Authenticate and get access token
            $authResponse = Http::post("{$baseUrl}/auth/hmac", [
                'application_id' => (int)$appId,
                'application_user_id' => (string)$userId,
                'signature' => $signature,
            ]);

            if ($authResponse->failed()) {
                Log::error('Spike auth failed', ['response' => $authResponse->body()]);
                return back()->with('t-error', 'Authentication failed: ' . $authResponse->body());
            }

            $accessToken = $authResponse->json('access_token');


            if (!$accessToken) {
                Log::error('Spike auth missing access token', ['response' => $authResponse->body()]);
                return back()->with('t-error', 'Authentication did not return access token.');
            }

            // 3️⃣ Prepare payload
            $payload = [
                'body' => $fileContents,
                'filename' => $fileName,
                'wait_on_process' => true,
            ];

            // 4️⃣ Upload lab report
            $uploadResponse = Http::withToken($accessToken)->timeout(600) // 10 minutes
                ->post("{$baseUrl}/lab_reports", $payload);

            if ($uploadResponse->failed()) {
                Log::error('Spike upload failed', ['response' => $uploadResponse->body()]);
                return back()->with('t-error', 'Upload failed: ' . $uploadResponse->body());
            }

            $labReportData = $uploadResponse->json('lab_report');

            if (!$labReportData) {
                Log::warning('Spike upload returned unexpected response', ['response' => $uploadResponse->body()]);
                return back()->with('t-error', 'Upload succeeded but lab_report data missing.');
            }

            // 5️⃣ Normalize patient DOB
            $dob = $labReportData['patient_information']['date_of_birth'] ?? null;
            if ($dob && preg_match('/^\d{4}$/', $dob)) {
                $dob = $dob . '-01-01';
            }
            $dob = $labReportData['patient_information']['date_of_birth'] ?? null;
            $collectionDate = $labReportData['collection_date'] ?? null;

            // Normalize DOB if only year is provided
            if ($dob && preg_match('/^\d{4}$/', $dob)) {
                $dob .= '-01-01';
            }

            // Calculate chronological age
            $chronologicalAge = null;
            if ($dob && $collectionDate) {
                $dobDate = new \DateTime($dob);
                $testDate = new \DateTime($collectionDate);
                $chronologicalAge = $dobDate->diff($testDate)->y;
            }

            // Helper to find a test value in sections
            function findTestValue($sections, $testName)
            {
                foreach ($sections as $section) {
                    if (!empty($section['results'])) {
                        foreach ($section['results'] as $result) {
                            if (strcasecmp($result['original_test_name'] ?? '', $testName) === 0) {
                                return $result['value'] ?? null;
                            }
                        }
                    }
                }
                return null;
            }

            $sections = $labReportData['sections'] ?? [];

            // Now map all necessary fields
            LabReport::create([
                'user_id' => $userId,
                'record_id' => $labReportData['record_id'],
                'file_path' => $file->getPathname() ?? 'empty',
                'patient_name' => $labReportData['patient_information']['name'] ?? null,
                'date_of_birth' => $dob,
                'test_date' => $collectionDate,
                'chronological_age' => $chronologicalAge,
                'total_delta' => $labReportData['total_delta'] ?? null,
                'blue_age' => $labReportData['blue_age'] ?? null,
                'interpretation' => $labReportData['interpretation'] ?? null,

                // Metabolic Panel
                'fasting_glucose' => findTestValue($sections, 'Glucose'),
                'hba1c' => findTestValue($sections, 'Hemoglobin A1c'),
                'fasting_insulin' => findTestValue($sections, 'Insulin'),
                'homa_ir' => null, // you may calculate this from glucose & insulin

                // Liver Function
                'alt' => findTestValue($sections, 'ALT (SGPT)'),
                'ast' => findTestValue($sections, 'AST (SGOT)'),
                'ggt' => findTestValue($sections, 'GGT'),

                // Kidney Function
                'serum_creatinine' => findTestValue($sections, 'Creatinine'),
                'egfr' => findTestValue($sections, 'eGFR'),

                // Inflammation Markers
                'hs_crp' => findTestValue($sections, 'hs-CRP'),
                'homocysteine' => findTestValue($sections, 'Homocysteine'),

                // Lipid Panel
                'triglycerides' => findTestValue($sections, 'Triglycerides'),
                'hdl_cholesterol' => findTestValue($sections, 'HDL Cholesterol'),
                'lp_a' => findTestValue($sections, 'Lp(a)'),

                // Hematologic Panel
                'wbc_count' => findTestValue($sections, 'WBC'),
                'lymphocyte_percentage' => findTestValue($sections, 'Lymphs'),
                'rdw' => findTestValue($sections, 'RDW'),
                'albumin' => findTestValue($sections, 'Albumin'),

                // Genetic Markers
                'apoe_genotype' => findTestValue($sections, 'APOE Genotype'),
                'mthfr_c677t' => findTestValue($sections, 'MTHFR C677T'),
            ]);


            return back()->with('t-success', 'Lab report uploaded and saved successfully!');
        } catch (\Exception $e) {
            Log::error('Exception uploading lab report', ['message' => $e->getMessage()]);
            return back()->with('t-error', 'Error uploading lab report: ' . $e->getMessage());
        }
    }




    function calculateBlueAge(array $patientData): array {
        // Official biomarker ranges and deltas from Blue Age Algorithm v1.0
        $biomarkers = [
            // === METABOLIC PANEL ===
            'fasting_glucose' => [
                ['range' => [0, 69.99], 'delta' => 1.0],
                ['range' => [70, 85], 'delta' => -2.0],
                ['range' => [85.01, 99], 'delta' => 0],
                ['range' => [100, 125], 'delta' => 2.0],
                ['range' => [125.01, 1000], 'delta' => 4.0],
            ],
            'hba1c' => [
                ['range' => [0, 3.99], 'delta' => 2.0],
                ['range' => [4.0, 4.7], 'delta' => 0.5],
                ['range' => [4.8, 5.3], 'delta' => -2.0],
                ['range' => [5.4, 5.6], 'delta' => 0],
                ['range' => [5.7, 6.4], 'delta' => 2.0],
                ['range' => [6.5, 100], 'delta' => 4.0],
            ],
            'fasting_insulin' => [
                ['range' => [0, 1.99], 'delta' => 1.0],
                ['range' => [2.0, 6.0], 'delta' => -2.0],
                ['range' => [6.1, 10.0], 'delta' => 0],
                ['range' => [10.1, 15.0], 'delta' => 2.0],
                ['range' => [15.1, 1000], 'delta' => 3.5],
            ],
            'homa_ir' => [
                ['range' => [0, 0.49], 'delta' => 0.5],
                ['range' => [0.5, 1.4], 'delta' => -2.0],
                ['range' => [1.5, 2.5], 'delta' => 0.5],
                ['range' => [2.6, 4.0], 'delta' => 2.5],
                ['range' => [4.01, 100], 'delta' => 4.0],
            ],

            // === LIVER FUNCTION PANEL ===
            'alt' => [
                ['range' => [0, 9.99], 'delta' => 1.0],
                ['range' => [10, 20], 'delta' => -1.5],
                ['range' => [21, 40], 'delta' => 0],
                ['range' => [41, 60], 'delta' => 1.5],
                ['range' => [61, 1000], 'delta' => 3.0],
            ],
            'ast' => [
                ['range' => [0, 9.99], 'delta' => 1.0],
                ['range' => [10, 20], 'delta' => -1.5],
                ['range' => [21, 39], 'delta' => 0],
                ['range' => [40, 60], 'delta' => 2.5],
                ['range' => [61, 1000], 'delta' => 4.0],
            ],
            'ggt' => [
                ['range' => [0, 8.99], 'delta' => 0],
                ['range' => [9, 25], 'delta' => -1.0],
                ['range' => [26, 44], 'delta' => 1.0],
                ['range' => [45, 1000], 'delta' => 3.0],
            ],

            // === KIDNEY FUNCTION PANEL ===
            'serum_creatinine' => [
                ['range' => [0, 0.59], 'delta' => 0.5],
                ['range' => [0.6, 0.9], 'delta' => -1.5],
                ['range' => [0.91, 1.1], 'delta' => 0],
                ['range' => [1.11, 1.3], 'delta' => 1.5],
                ['range' => [1.31, 100], 'delta' => 3.0],
            ],
            'egfr' => [
                ['range' => [0, 44], 'delta' => 4.0],
                ['range' => [45, 59], 'delta' => 2.5],
                ['range' => [60, 89], 'delta' => 1.0],
                ['range' => [90, 120], 'delta' => -1.5],
                ['range' => [121, 1000], 'delta' => -1.0],
            ],

            // === INFLAMMATORY MARKERS ===
            'hs_crp' => [
                ['range' => [0, 0.49], 'delta' => -2.0],
                ['range' => [0.5, 1.0], 'delta' => -1.0],
                ['range' => [1.01, 3.0], 'delta' => 1.0],
                ['range' => [3.01, 10.0], 'delta' => 3.0],
                ['range' => [10.01, 1000], 'delta' => 5.0],
            ],
            'homocysteine' => [
                ['range' => [0, 4.99], 'delta' => 0.5],
                ['range' => [5.0, 8.0], 'delta' => -2.0],
                ['range' => [8.1, 10.0], 'delta' => 0],
                ['range' => [10.1, 15.0], 'delta' => 2.0],
                ['range' => [15.1, 1000], 'delta' => 4.0],
            ],

            // === LIPID & CARDIOVASCULAR PANEL ===
            'triglycerides' => [
                ['range' => [0, 49.99], 'delta' => 0.5],
                ['range' => [50, 80], 'delta' => -1.5],
                ['range' => [81, 150], 'delta' => 0],
                ['range' => [151, 200], 'delta' => 1.5],
                ['range' => [201, 1000], 'delta' => 3.0],
            ],
            'hdl_cholesterol' => [
                ['range' => [0, 39.99], 'delta' => 2.5],
                ['range' => [40, 49], 'delta' => 1.0],
                ['range' => [50, 70], 'delta' => -1.5],
                ['range' => [71, 90], 'delta' => -1.0],
                ['range' => [91, 1000], 'delta' => 0],
            ],
            'lpa' => [
                ['range' => [0, 29.99], 'delta' => -1.0],
                ['range' => [30, 75], 'delta' => 0],
                ['range' => [76, 125], 'delta' => 1.5],
                ['range' => [126, 10000], 'delta' => 3.0],
            ],

            // === GENETIC MARKERS ===
            'apoe' => [
                'ε2/ε2' => -2.0,
                'ε2/ε3' => -1.5,
                'ε3/ε3' => 0,
                'ε3/ε4' => 2.0,
                'ε4/ε4' => 4.0,
            ],
            'mthfr' => [
                'CC' => 0,
                'CT' => 0.5,
                'TT' => 1.5,
            ],

            // === HEMATOLOGIC PANEL ===
            'rdw' => [
                ['range' => [0, 11.49], 'delta' => 0.5],
                ['range' => [11.5, 13.0], 'delta' => -1.0],
                ['range' => [13.1, 14.5], 'delta' => 1.0],
                ['range' => [14.51, 100], 'delta' => 2.5],
            ],
            'wbc_count' => [
                ['range' => [0, 3.99], 'delta' => 1.5],
                ['range' => [4.0, 6.0], 'delta' => -1.0],
                ['range' => [6.1, 9.0], 'delta' => 0],
                ['range' => [9.1, 11.0], 'delta' => 1.0],
                ['range' => [11.01, 1000], 'delta' => 2.5],
            ],
            'lymphocyte_percentage' => [
                ['range' => [0, 19.99], 'delta' => 2.0],
                ['range' => [20, 24], 'delta' => 0.5],
                ['range' => [25, 35], 'delta' => -1.0],
                ['range' => [36, 40], 'delta' => 0],
                ['range' => [41, 100], 'delta' => 0.5],
            ],
            'albumin' => [
                ['range' => [0, 3.39], 'delta' => 3.0],
                ['range' => [3.4, 3.9], 'delta' => 1.5],
                ['range' => [4.0, 4.8], 'delta' => -1.5],
                ['range' => [4.9, 5.4], 'delta' => 0],
                ['range' => [5.41, 100], 'delta' => 0.5],
            ],
        ];

        // Helper function to get delta for numeric biomarkers
        $getDelta = function($value, $ranges) {
            foreach ($ranges as $r) {
                if (isset($r['range']) && is_array($r['range']) && count($r['range']) === 2) {
                    if ($value >= $r['range'][0] && $value <= $r['range'][1]) {
                        return $r['delta'];
                    }
                }
            }
            return 0;
        };

        // Start with chronological age
        $chronologicalAge = $patientData['chronological_age'] ?? 0;
        $blueAge = $chronologicalAge;
        $appliedDeltas = [];

        // Calculate HOMA-IR if fasting glucose and insulin are available
        if (isset($patientData['fasting_glucose']) && isset($patientData['fasting_insulin'])) {
            $patientData['homa_ir'] = ($patientData['fasting_glucose'] * $patientData['fasting_insulin']) / 405;
        }

        // Process each biomarker
        foreach ($biomarkers as $key => $ranges) {
            if (!is_array($ranges) || count($ranges) === 0) {
                continue;
            }

            // Get first element to check type
            $firstElement = reset($ranges);

            if (is_array($firstElement) && isset($firstElement['range'])) {
                // Numeric biomarker with ranges
                if (isset($patientData[$key]) && is_numeric($patientData[$key])) {
                    $delta = $getDelta($patientData[$key], $ranges);
                    $blueAge += $delta;
                    if ($delta != 0) {
                        $appliedDeltas[$key] = [
                            'value' => $patientData[$key],
                            'delta' => $delta
                        ];
                    }
                }
            } else {
                // Genetic biomarker (key-value pairs)
                if (isset($patientData[$key]) && isset($ranges[$patientData[$key]])) {
                    $delta = $ranges[$patientData[$key]];
                    $blueAge += $delta;
                    if ($delta != 0) {
                        $appliedDeltas[$key] = [
                            'value' => $patientData[$key],
                            'delta' => $delta
                        ];
                    }
                }
            }
        }

        // Calculate optimal range (best case to worst case scenario)
        $minDelta = 0;
        $maxDelta = 0;

        foreach ($biomarkers as $key => $ranges) {
            if (!is_array($ranges) || count($ranges) === 0) {
                continue;
            }

            $firstElement = reset($ranges);

            if (is_array($firstElement) && isset($firstElement['delta'])) {
                // Numeric biomarkers - get min and max deltas
                $deltas = array_column($ranges, 'delta');
                $minDelta += min($deltas);
                $maxDelta += max($deltas);
            } else {
                // Genetic biomarkers - get min and max values
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
            'blue_age' => round($blueAge, 1),
            'chronological_age' => $chronologicalAge,
            'optimal_range' => $optimalRange,
            'last_updated' => date('F d, Y'),
            'applied_deltas' => $appliedDeltas,
            'total_delta' => round($blueAge - $chronologicalAge, 1),
        ];
    }


/**
 * Calculate and store Blue Age for authenticated user
 * Includes fitness and lifestyle adjustments
 */
function calculateAndStore()
{
    // Fetch the latest lab report for the authenticated user
    $report = LabReport::where('user_id', auth('api')->id())->latest()->first();

    if (!$report) {
        return response()->json(['error' => 'No lab reports found for this user.'], 404);
    }

    // Prepare patient data
    $patientData = [
        'chronological_age' => $report->chronological_age,
        'fasting_glucose' => $report->fasting_glucose,
        'hba1c' => $report->hba1c,
        'fasting_insulin' => $report->fasting_insulin,
        'alt' => $report->alt,
        'ast' => $report->ast,
        'ggt' => $report->ggt,
        'serum_creatinine' => $report->serum_creatinine,
        'egfr' => $report->egfr,
        'hs_crp' => $report->hs_crp,
        'homocysteine' => $report->homocysteine,
        'triglycerides' => $report->triglycerides,
        'hdl_cholesterol' => $report->hdl_cholesterol,
        'lpa' => $report->lpa,
        'apoe' => $report->apoe_genotype,
        'mthfr' => $report->mthfr_c677t,
        'rdw' => $report->rdw,
        'wbc_count' => $report->wbc_count,
        'lymphocyte_percentage' => $report->lymphocyte_percentage,
        'albumin' => $report->albumin,
        'vo2max' => $report->vo2max,
        'hrv' => $report->hrv,
        'lifestyle_delta' => $report->lifestyle_delta ?? 0,
    ];

    // Calculate Blue Age
    $blueAgeResult = $this->calculateBlueAge($patientData);

    $chronAge = $patientData['chronological_age'];
    $coreLabAge = $blueAgeResult['blue_age'];
    $deltaAge = round($coreLabAge - $chronAge, 1);

    // === FITNESS ADJUSTMENT ===
    // Expected VO2max: 60 - (0.5 × age) for males
    // Expected HRV: 100 - (0.8 × age)
    $expectedVO2 = 60 - (0.5 * $chronAge);
    $expectedHRV = 100 - (0.8 * $chronAge);

    $fitnessAdj = 0;

    // VO2max adjustment: If below expected, add 0.1 years per unit difference
    if (isset($patientData['vo2max']) && is_numeric($patientData['vo2max'])) {
        if ($patientData['vo2max'] < $expectedVO2) {
            $fitnessAdj += ($expectedVO2 - $patientData['vo2max']) * 0.1;
        }
    }

    // === LIFESTYLE ADJUSTMENT ===
    $lifestyleAdj = $patientData['lifestyle_delta'] ?? 0;

    // === FINAL BLUEGRASS AGE ===
    $finalBluegrassAge = round($coreLabAge + $fitnessAdj + $lifestyleAdj, 1);

    // Prepare result
    $result = [
        'blue_age' => $finalBluegrassAge,
        'optimal_range' => $blueAgeResult['optimal_range'],
        'last_updated' => $blueAgeResult['last_updated'],
        'delta_age' => $deltaAge,
        'core_lab_age' => $coreLabAge,
        'fitness_adj' => round($fitnessAdj, 1),
        'lifestyle_adj' => round($lifestyleAdj, 1),
        'expected_vo2max' => round($expectedVO2, 1),
        'expected_hrv' => round($expectedHRV, 1),
        'applied_deltas' => $blueAgeResult['applied_deltas'],
        'interpretation' => $this->interpretBlueAge($finalBluegrassAge, $chronAge),
    ];

    return response()->json([
        'message' => 'Blue Age calculated successfully.',
        'user_id' => auth('api')->id(),
        'report' => $result
    ]);
}

/**
 * Interpret Blue Age result based on official guidelines
 */
function interpretBlueAge($blueAge, $chronologicalAge): array
{
    $difference = $blueAge - $chronologicalAge;

    if ($difference <= -5) {
        return [
            'category' => 'Excellent',
            'description' => 'Excellent biological profile, low mortality risk',
            'color' => '#22c55e', // green
            'recommendation' => 'Maintain current lifestyle and biomarker optimization'
        ];
    } elseif ($difference > -5 && $difference < -2) {
        return [
            'category' => 'Good',
            'description' => 'Better than average aging trajectory',
            'color' => '#3b82f6', // blue
            'recommendation' => 'Continue healthy habits with minor optimization'
        ];
    } elseif ($difference >= -2 && $difference <= 2) {
        return [
            'category' => 'Average',
            'description' => 'Average aging trajectory',
            'color' => '#f59e0b', // amber
            'recommendation' => 'Consider lifestyle improvements for better aging outcomes'
        ];
    } elseif ($difference > 2 && $difference <= 5) {
        return [
            'category' => 'Accelerated',
            'description' => 'Accelerated aging, lifestyle intervention recommended',
            'color' => '#f97316', // orange
            'recommendation' => 'Implement targeted interventions for high-impact biomarkers'
        ];
    } else {
        return [
            'category' => 'Significant',
            'description' => 'Significant biological aging, comprehensive intervention needed',
            'color' => '#ef4444', // red
            'recommendation' => 'Comprehensive medical evaluation and aggressive intervention required'
        ];
    }
}
}
