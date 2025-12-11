<?php

namespace App\Jobs;

use App\Models\LabReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class PdfStoreJobs implements ShouldQueue
{
    use Queueable;

     // Add class properties
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

        // Proper logging: second argument must be an array
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
        //

         $uploadResponse = Http::withToken($this->accessToken )->timeout(600) // 10 minutes
                ->post("{$this->baseUrl}/lab_reports", $this->payload);



            $labReportData = $uploadResponse->json('lab_report');


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
                'user_id' =>  $this->userId,
                'record_id' => $labReportData['record_id'],
                'file_path' => 'empty',
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
    }
}
