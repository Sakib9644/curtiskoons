<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\LabReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LabReportController extends Controller
{
    /**
     * List all lab reports for all users (admin view)
     */
    public function index(Request $request)
    {
        $users = User::with('labReports')->orderBy('created_at', 'desc')->get();
        return view('lab_reports.index', compact('users'));
    }

    /**
     * Show user lab reports (optional, for individual user)
     */
    public function show(User $user)
    {
        $labReports = $user->labReports()->latest()->get();
        return view('lab_reports.show', compact('user', 'labReports'));
    }

    /**
     * Upload lab report form (modal handled in frontend)
     */
    public function create(User $user)
    {
        return view('lab_reports.create', compact('user'));
    }

    /**
     * Store uploaded lab report and optionally call Spike OCR
     */
   public function store(Request $request, User $user)
{
    $request->validate([
        'lab_report' => 'required|file|mimes:pdf,jpg,png',
    ]);

    $filePath = null;

    if ($request->hasFile('lab_report')) {
        $file = $request->file('lab_report');

        // Optional: delete previous file if you have any
        if (!empty($user->labReport?->file_path)) {
            Helper::fileDelete(public_path($user->labReport->file_path));
        }

        // Upload using Helper::fileUpload
        $filePath = Helper::fileUpload($file, 'lab_reports', getFileName($file));
    }

    $labReport = LabReport::create([
        'patient_id' => $user->id,
        'file_path' => $filePath,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    // Optional: process OCR
    $this->processWithSpike($labReport);

    return redirect()->back()->with('success', 'Lab report uploaded successfully.');
}


    /**
     * Process lab report with Spike OCR API
     */
    protected function processWithSpike(LabReport $labReport)
{
    // Correct file path
    $filePath = public_path($labReport->file_path); // points to public/lab_reports/filename.pdf

    $url = env('SPIKE_API_BASE_URL') . '/lab-ocr';

    $response = Http::withHeaders([
        'X-Application-ID' => env('SPIKE_APPLICATION_ID'),
        'X-HMAC-Key' => env('SPIKE_HMAC_KEY')
    ])->attach(
        'file',
        file_get_contents($filePath),
        basename($filePath)
    )->post($url, [
        'patient_id' => $labReport->patient_id
    ]);
    dd($response->json() );

    if ($response->ok()) {
        $labReport->update([
            'extracted_data' => $response->json(),
            'status' => 'pending', // or 'processed' if you want to mark it
        ]);
    }
}


    /**
     * Admin review page for lab report
     */
    public function review(LabReport $labReport)
    {
        return view('lab_reports.review', compact('labReport'));
    }

    /**
     * Publish lab report after admin approves
     */
    public function publish(Request $request, LabReport $labReport)
    {
        $labReport->update([
            'extracted_data' => $request->only(array_keys($labReport->extracted_data ?? [])),
            'status' => 'approved'
        ]);

        return redirect()->route('users.show', $labReport->patient_id)
            ->with('success', 'Lab report published successfully.');
    }
}
