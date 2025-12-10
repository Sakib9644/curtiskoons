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
            dd(    $labReportData );

            if (!$labReportData) {
                Log::warning('Spike upload returned unexpected response', ['response' => $uploadResponse->body()]);
                return back()->with('t-error', 'Upload succeeded but lab_report data missing.');
            }

            // 5️⃣ Normalize patient DOB
            $dob = $labReportData['patient_information']['date_of_birth'] ?? null;
            if ($dob && preg_match('/^\d{4}$/', $dob)) {
                $dob = $dob . '-01-01';
            }

            // 6️⃣ Store in DB
            LabReport::create([
                'user_id' => $userId,
                'file_path' => $file->getPathname(),
                'patient_name' => $labReportData['patient_information']['name'] ?? null,
                'date_of_birth' => $dob,
                'test_date' => $labReportData['test_date'] ?? null,
                'chronological_age' => $labReportData['chronological_age'] ?? null,
                'total_delta' => $labReportData['total_delta'] ?? null,
                'blue_age' => $labReportData['blue_age'] ?? null,
                'interpretation' => $labReportData['interpretation'] ?? null,

                // Metabolic Panel
                'fasting_glucose' => $labReportData['metabolic_panel']['fasting_glucose'] ?? null,
                'hba1c' => $labReportData['metabolic_panel']['hba1c'] ?? null,
                'fasting_insulin' => $labReportData['metabolic_panel']['fasting_insulin'] ?? null,
                'homa_ir' => $labReportData['metabolic_panel']['homa_ir'] ?? null,

                // Liver Function
                'alt' => $labReportData['liver_function']['alt'] ?? null,
                'ast' => $labReportData['liver_function']['ast'] ?? null,
                'ggt' => $labReportData['liver_function']['ggt'] ?? null,

                // Kidney Function
                'serum_creatinine' => $labReportData['kidney_function']['serum_creatinine'] ?? null,
                'egfr' => $labReportData['kidney_function']['egfr'] ?? null,

                // Inflammation Markers
                'hs_crp' => $labReportData['inflammation_markers']['hs_crp'] ?? null,
                'homocysteine' => $labReportData['inflammation_markers']['homocysteine'] ?? null,

                // Lipid Panel
                'triglycerides' => $labReportData['lipid_panel']['triglycerides'] ?? null,
                'hdl_cholesterol' => $labReportData['lipid_panel']['hdl_cholesterol'] ?? null,
                'lp_a' => $labReportData['lipid_panel']['lp_a'] ?? null,

                // Hematologic Panel
                'wbc_count' => $labReportData['hematologic_panel']['wbc_count'] ?? null,
                'lymphocyte_percentage' => $labReportData['hematologic_panel']['lymphocyte_percentage'] ?? null,
                'rdw' => $labReportData['hematologic_panel']['rdw'] ?? null,
                'albumin' => $labReportData['hematologic_panel']['albumin'] ?? null,

                // Genetic Markers
                'apoe_genotype' => $labReportData['genetic_markers']['apoe_genotype'] ?? null,
                'mthfr_c677t' => $labReportData['genetic_markers']['mthfr_c677t'] ?? null,
            ]);

            return back()->with('t-success', 'Lab report uploaded and saved successfully!');
        } catch (\Exception $e) {
            Log::error('Exception uploading lab report', ['message' => $e->getMessage()]);
            return back()->with('t-error', 'Error uploading lab report: ' . $e->getMessage());
        }
    }
}
