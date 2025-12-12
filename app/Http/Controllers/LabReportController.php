<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use App\Models\User;
use Carbon\Carbon;
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
    $report = LabReport::where('user_id', auth('api')->id())->latest()->first();

    if (!$report) {
        return response()->json(['error' => 'No lab reports found for this user.'], 404);
    }

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

    $blueAgeResult = calculateBlueAge($patientData);

    $chronAge = $patientData['chronological_age'];
    $coreLabAge = $blueAgeResult['blue_age'];
    $deltaAge = round($coreLabAge - $chronAge, 1);

    $expectedVO2 = 60 - (0.5 * $chronAge);
    $expectedHRV = 100 - (0.8 * $chronAge);

    $fitnessAdj = 0;
    if (isset($patientData['vo2max']) && $patientData['vo2max'] < $expectedVO2) {
        $fitnessAdj = ($expectedVO2 - $patientData['vo2max']) * 0.1;
    }

    $lifestyleAdj = $patientData['lifestyle_delta'] ?? 0;
    $finalBluegrassAge = round($coreLabAge + $fitnessAdj + $lifestyleAdj, 1);

    $report->blue_age = $finalBluegrassAge;
    $report->save();

    $reportData = [
        'blue_age' => $finalBluegrassAge,
        'chronological_age' => (int)$blueAgeResult['chronological_age'],
        'optimal_range' => $blueAgeResult['optimal_range'],
        'last_updated' => Carbon::parse($report->test_date)->format('F j, Y'),
        'delta_age' => $deltaAge,
        'core_lab_age' => $coreLabAge,
        'fitness_adj' => round($fitnessAdj, 1),
        'lifestyle_adj' => round($lifestyleAdj, 1),
        'expected_vo2max' => round($expectedVO2, 1),
        'expected_hrv' => round($expectedHRV, 1),
    ];

    return response()->json([
        'message' => 'Blue Age and all components calculated and saved for the latest lab report of the user.',
        'user_id' => auth('api')->id(),
        'report' => $reportData
    ]);
}


    public function allblueagereports()
    {
        $reports = LabReport::where('user_id', auth('api')->id())->get();

        if ($reports->isEmpty()) {
            return response()->json(['error' => 'No lab reports found for this user.'], 404);
        }


            $blue = [];
            $testdate = [];
             $chronAge  = [];

        foreach ($reports as $singleReport) {

            // Prepare patient data for BlueAge calculation
            $patientData = [
                'chronological_age' => $singleReport->chronological_age,
                'fasting_glucose' => $singleReport->fasting_glucose,
                'hba1c' => $singleReport->hba1c,
                'fasting_insulin' => $singleReport->fasting_insulin,
                'alt' => $singleReport->alt,
                'ast' => $singleReport->ast,
                'ggt' => $singleReport->ggt,
                'serum_creatinine' => $singleReport->serum_creatinine,
                'egfr' => $singleReport->egfr,
                'hs_crp' => $singleReport->hs_crp,
                'homocysteine' => $singleReport->homocysteine,
                'triglycerides' => $singleReport->triglycerides,
                'hdl_cholesterol' => $singleReport->hdl_cholesterol,
                'lpa' => $singleReport->lp_a,
                'apoe' => $singleReport->apoe_genotype,
                'mthfr' => $singleReport->mthfr_c677t,
                'rdw' => $singleReport->rdw,
                'wbc_count' => $singleReport->wbc_count,
                'lymphocyte_percentage' => $singleReport->lymphocyte_percentage,
                'albumin' => $singleReport->albumin,
                'vo2max' => $singleReport->vo2max,
                'hrv' => $singleReport->hrv,
                'lifestyle_delta' => $singleReport->lifestyle_delta ?? 0,
            ];

            // Blue Age Calculation Core
            $blueAgeResult = calculateBlueAge($patientData);

            $chronAge = $patientData['chronological_age'];
            $coreLabAge = $blueAgeResult['blue_age'];
            $deltaAge = round($coreLabAge - $chronAge, 1);

            // Fitness Adjustments
            $expectedVO2 = 60 - (0.5 * $chronAge);
            $expectedHRV = 100 - (0.8 * $chronAge);

            $fitnessAdj = 0;
            if ($patientData['vo2max'] < $expectedVO2) {
                $fitnessAdj = ($expectedVO2 - $patientData['vo2max']) * 0.1;
            }

            $lifestyleAdj = $patientData['lifestyle_delta'] ?? 0;

            $finalBluegrassAge = round($coreLabAge + $fitnessAdj + $lifestyleAdj, 1);

            $singleReport->blue_age = $finalBluegrassAge;
            $singleReport->save();



            $chronAge[] = $chronAge;
            $blue[] = $finalBluegrassAge;
            $testdate[] = $singleReport['test_date'];

        }

        return response()->json([
            'message' => 'All Blue Age Reports Calculated Successfully.',
            'blue' => $blue,
            'chron_Age=' => $chronAge ,
            'testdate' => $testdate,

        ]);
    }

    public function report($id)
    {

        $user = User::find($id);

        $report = LabReport::select('id', 'test_date')->where('user_id', $user->id)->get();
        return view('backend.lab-reports.reports', compact('report'));
    }

    public function edit($id)
    {

        $report = LabReport::find($id);

        return view('backend.lab-reports.edit', compact('report'));
    }

    public function update(Request $request,  $id)
    {
        $data = $request->validate([
            'patient_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'test_date' => 'nullable|date',
            'chronological_age' => 'nullable|numeric',

            'fasting_glucose' => 'nullable|numeric',
            'hba1c' => 'nullable|numeric',
            'fasting_insulin' => 'nullable|numeric',
            'homa_ir' => 'nullable|numeric',

            'alt' => 'nullable|numeric',
            'ast' => 'nullable|numeric',
            'ggt' => 'nullable|numeric',

            'serum_creatinine' => 'nullable|numeric',
            'egfr' => 'nullable|numeric',

            'hs_crp' => 'nullable|numeric',
            'homocysteine' => 'nullable|numeric',

            'triglycerides' => 'nullable|numeric',
            'hdl_cholesterol' => 'nullable|numeric',
            'lp_a' => 'nullable|numeric',

            'wbc_count' => 'nullable|numeric',
            'lymphocyte_percentage' => 'nullable|numeric',
            'rdw' => 'nullable|numeric',
            'albumin' => 'nullable|numeric',

            'apoe_genotype' => 'nullable|string|max:10',
            'mthfr_c677t' => 'nullable|string|max:5',
        ]);

        $report = LabReport::find($id);

        $report->update($data);


        return redirect()->back()->with('t-success', 'Lab report updated successfully.');
    }


}
