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

    protected $accessToken;
    protected $payload;
    protected $baseUrl;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($accessToken, $payload, $baseUrl, $userId)
    {
        $this->accessToken = $accessToken;
        $this->payload = $payload;
        $this->baseUrl = $baseUrl;
        $this->userId = $userId;

        Log::info('PdfStoreJobs initialized', [
            'user_id' => $this->userId,
            'payload' => $this->payload,
            'base_url' => $this->baseUrl,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Upload lab report to Spike API
            $uploadResponse = Http::withToken($this->accessToken)
                ->timeout(600) // 10 minutes
                ->post("{$this->baseUrl}/lab_reports", $this->payload);

            $labReportData = $uploadResponse->json('lab_report');

            if (!$labReportData) {
                Log::error('PdfStoreJobs: missing lab_report in API response', [
                    'response' => $uploadResponse->body(),
                ]);
                return;
            }

            // Normalize DOB
            $dob = $labReportData['patient_information']['date_of_birth'] ?? null;
            if ($dob && preg_match('/^\d{4}$/', $dob)) {
                $dob .= '-01-01';
            }

            $collectionDate = $labReportData['collection_date'] ?? null;

            // Calculate chronological age
            $chronologicalAge = null;
            if ($dob && $collectionDate) {
                $dobDate = new \DateTime($dob);
                $testDate = new \DateTime($collectionDate);
                $chronologicalAge = $dobDate->diff($testDate)->y;
            }

            $sections = $labReportData['sections'] ?? [];

            // Save to database
            LabReport::create([
                'user_id' =>  $this->userId,
                'record_id' => $labReportData['record_id'] ?? null,
                'file_path' => 'empty',
                'patient_name' => $labReportData['patient_information']['name'] ?? null,
                'date_of_birth' => $dob,
                'test_date' => $collectionDate,
                'chronological_age' => $chronologicalAge,
                'total_delta' => $labReportData['total_delta'] ?? null,
                'blue_age' => $labReportData['blue_age'] ?? null,
                'interpretation' => $labReportData['interpretation'] ?? null,

                // Metabolic Panel
                'fasting_glucose' => $this->findTestValue($sections, 'Glucose'),
                'hba1c' => $this->findTestValue($sections, 'Hemoglobin A1c'),
                'fasting_insulin' => $this->findTestValue($sections, 'Insulin'),
                'homa_ir' => null, // optionally calculate

                // Liver Function
                'alt' => $this->findTestValue($sections, 'ALT (SGPT)'),
                'ast' => $this->findTestValue($sections, 'AST (SGOT)'),
                'ggt' => $this->findTestValue($sections, 'GGT'),

                // Kidney Function
                'serum_creatinine' => $this->findTestValue($sections, 'Creatinine'),
                'egfr' => $this->findTestValue($sections, 'eGFR'),

                // Inflammation Markers
                'hs_crp' => $this->findTestValue($sections, 'hs-CRP'),
                'homocysteine' => $this->findTestValue($sections, 'Homocysteine'),

                // Lipid Panel
                'triglycerides' => $this->findTestValue($sections, 'Triglycerides'),
                'hdl_cholesterol' => $this->findTestValue($sections, 'HDL Cholesterol'),
                'lp_a' => $this->findTestValue($sections, 'Lp(a)'),

                // Hematologic Panel
                'wbc_count' => $this->findTestValue($sections, 'WBC'),
                'lymphocyte_percentage' => $this->findTestValue($sections, 'Lymphs'),
                'rdw' => $this->findTestValue($sections, 'RDW'),
                'albumin' => $this->findTestValue($sections, 'Albumin'),

                // Genetic Markers
                'apoe_genotype' => $this->findTestValue($sections, 'APOE Genotype'),
                'mthfr_c677t' => $this->findTestValue($sections, 'MTHFR C677T'),
            ]);

            Log::info('PdfStoreJobs completed successfully', ['user_id' => $this->userId]);
        } catch (\Exception $e) {
            Log::error('PdfStoreJobs failed', ['message' => $e->getMessage()]);
            throw $e; // allow queue to handle retries
        }
    }

    /**
     * Find a test value in sections.
     */
    private function findTestValue(array $sections, string $testName)
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
}
