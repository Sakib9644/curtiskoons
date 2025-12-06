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
        $uploadResponse = Http::withToken($accessToken)->post("{$baseUrl}/lab_reports", $payload);

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
            // Only year provided, append "-01-01"
            $dob = $dob . '-01-01';
        }

        // 6️⃣ Store in DB
        LabReport::create([
            'user_id' => $userId,
            'file_path' => $fileName,
            'record_id' => $labReportData['record_id'] ?? null,
            'status' => $labReportData['status'] ?? null,
            'collection_date' => $labReportData['collection_date'] ?? null,
            'result_date' => $labReportData['result_date'] ?? null,
            'report_notes' => $labReportData['notes'] ?? null,
            'patient_id' => $labReportData['patient_information']['patient_id'] ?? null,
            'patient_name' => $labReportData['patient_information']['patient_name'] ?? null,
            'patient_dob' => $dob, // normalized
            'patient_gender' => $labReportData['patient_information']['gender'] ?? null,
            'lab_name' => $labReportData['lab_information']['name'] ?? null,
            'lab_address' => $labReportData['lab_information']['address'] ?? null,
            'lab_phone' => $labReportData['lab_information']['phone_number'] ?? null,
            'lab_notes' => $labReportData['lab_information']['notes'] ?? null,
            'sections' => $labReportData['sections'] ?? [], // store as JSON string
        ]);

        return back()->with('t-success', 'Lab report uploaded and saved successfully!');

    } catch (\Exception $e) {
        Log::error('Exception uploading lab report', ['message' => $e->getMessage()]);
        return back()->with('t-error', 'Error uploading lab report: ' . $e->getMessage());
    }
}

}
