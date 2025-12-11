<?php

namespace App\Jobs;

use App\Models\LabReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdfStoreJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileName;
    protected $fileContents;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($fileName, $fileContents, $userId)
    {
        $this->fileName = $fileName;
        $this->fileContents = $fileContents;
        $this->userId = $userId;

        // Log basic info only
        Log::info('PdfStoreJobs initialized', [
            'file_name' => $this->fileName,
            'file_size' => strlen($this->fileContents),
            'user_id' => $this->userId
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $appId = env('SPIKE_APPLICATION_ID');
        $hmacKey = env('SPIKE_HMAC_KEY');
        $baseUrl = env('SPIKE_API_BASE_URL', 'https://app-api.spikeapi.com/v3');

        try {
            // 1️⃣ Generate HMAC signature
            $signature = hash_hmac('sha256', (string)$this->userId, $hmacKey);

            // 2️⃣ Authenticate and get access token
            $authResponse = Http::post("{$baseUrl}/auth/hmac", [
                'application_id' => (int)$appId,
                'application_user_id' => (string)$this->userId,
                'signature' => $signature,
            ]);

            if ($authResponse->failed()) {
                Log::error('Spike auth failed', ['response' => $authResponse->body()]);
                return;
            }

            $accessToken = $authResponse->json('access_token');
            if (!$accessToken) {
                Log::error('Spike auth missing access token', ['response' => $authResponse->body()]);
                return;
            }

            // 3️⃣ Prepare payload
            $payload = [
                'body' => $this->fileContents,
                'filename' => $this->fileName,
                'wait_on_process' => true,
            ];

            // 4️⃣ Upload lab report
            $uploadResponse = Http::withToken($accessToken)->timeout(600)
                ->post("{$baseUrl}/lab_reports", $payload);

            if ($uploadResponse->failed()) {
                Log::error('Spike upload failed', ['response' => $uploadResponse->body()]);
                return;
            }

            $labReportData = $uploadResponse->json('lab_report');

            if (!$labReportData) {
                Log::warning('Spike upload returned unexpected response', ['response' => $uploadResponse->body()]);
                return;
            }

            // Helper to find a test value in sections
            $sections = $labReportData['sections'] ?? [];
            $findTestValue = function ($sections, $testName) {
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
            };

            $dob = $labReportData['patient_information']['date_of_birth'] ?? null;
            $collectionDate = $labReportData['collection_date'] ?? null;

            if ($dob && preg_match('/^\d{4}$/', $dob)) {
                $dob .= '-01-01';
            }

            $chronologicalAge = null;
            if ($dob && $collectionDate) {
                $dobDate = new \DateTime($dob);
                $testDate = new \DateTime($collectionDate);
                $chronologicalAge = $dobDate->diff($testDate)->y;
            }

            // Save LabReport safely
            $labreport = LabReport::create([
                'user_id' => $this->userId,
                'record_id' => $labReportData['record_id'] ?? null,
                'file_path' => 'demo',
                'patient_name' => $labReportData['patient_information']['name'] ?? null,
                'date_of_birth' => $dob,
                'test_date' => $collectionDate,
                'chronological_age' => $chronologicalAge,
                'total_delta' => $labReportData['total_delta'] ?? null,
                'blue_age' => $labReportData['blue_age'] ?? null,
                'interpretation' => $labReportData['interpretation'] ?? null,

                // Metabolic Panel
                'fasting_glucose' => $findTestValue($sections, 'Glucose'),
                'hba1c' => $findTestValue($sections, 'Hemoglobin A1c'),
                'fasting_insulin' => $findTestValue($sections, 'Insulin'),
                'homa_ir' => null,

                // Liver Function
                'alt' => $findTestValue($sections, 'ALT (SGPT)'),
                'ast' => $findTestValue($sections, 'AST (SGOT)'),
                'ggt' => $findTestValue($sections, 'GGT'),

                // Kidney Function
                'serum_creatinine' => $findTestValue($sections, 'Creatinine'),
                'egfr' => $findTestValue($sections, 'eGFR'),

                // Inflammation Markers
                'hs_crp' => $findTestValue($sections, 'hs-CRP'),
                'homocysteine' => $findTestValue($sections, 'Homocysteine'),

                // Lipid Panel
                'triglycerides' => $findTestValue($sections, 'Triglycerides'),
                'hdl_cholesterol' => $findTestValue($sections, 'HDL Cholesterol'),
                'lp_a' => $findTestValue($sections, 'Lp(a)'),

                // Hematologic Panel
                'wbc_count' => $findTestValue($sections, 'WBC'),
                'lymphocyte_percentage' => $findTestValue($sections, 'Lymphs'),
                'rdw' => $findTestValue($sections, 'RDW'),
                'albumin' => $findTestValue($sections, 'Albumin'),

                // Genetic Markers
                'apoe_genotype' => $findTestValue($sections, 'APOE Genotype'),
                'mthfr_c677t' => $findTestValue($sections, 'MTHFR C677T'),
            ]);

            Log::info('Lab report created successfully', [
                'user_id' => $this->userId,
                'lab_report_id' => $labreport->id
            ]);

        } catch (\Exception $e) {
            Log::error('Exception uploading lab report', ['message' => $e->getMessage()]);
        }
    }
}
