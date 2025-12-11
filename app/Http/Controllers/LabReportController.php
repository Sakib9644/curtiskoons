<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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


public function calculateAndStore()
{
    // Fetch the latest lab report for the authenticated user
    $report = LabReport::where('user_id', auth('api')->id())->latest()->first();

    if (!$report) {
        return response()->json(['error' => 'No lab reports found for this user.'], 404);
    }

    // Calculate chronological age from birth date
    $user = auth('api')->user();
    $chronologicalAge = calculateChronologicalAge($user->birth_date);

    // Prepare patient data
    $patientData = [
        'chronological_age' => $chronologicalAge,
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

    // Calculate everything - now returns blue_age!
    $result = calculateBlueAge($patientData);

    // Update the report in database
    $report = [
        'chronological_age' => $result['chronological_age'],
        'blue_age' => $result['blue_age'], // ← This now exists!
        'core_lab_age' => $result['core_lab_age'],
        'delta_age' => $result['delta_age'],
        'optimal_range' => $result['optimal_range'],
        'fitness_adj' => $result['fitness_adj'],
        'lifestyle_adj' => $result['lifestyle_adj'],
        'expected_vo2max' => $result['expected_vo2max'],
        'expected_hrv' => $result['expected_hrv'],
        'last_updated' => now(),
    ];

    return response()->json([
        'message' => 'Blue Age calculated and saved successfully.',
        'user_id' => auth('api')->id(),
        'data' => $result
    ]);
}
}
