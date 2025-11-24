<?php

namespace App\Http\Controllers;

use App\Models\SpikeMetric;
use App\Models\User;
use Illuminate\Http\Request;

class SpikeWebhookController extends Controller
{
    // Read HMAC key from env
    private $hmacKey;



    public function handle(Request $request)
{
    Log::info($request->all());
    // Get incoming JSON data
    $events = $request->json()->all();

    if (!is_array($events)) {
        Log::warning('Spike webhook data is not an array: ' . json_encode($events));
        return response('Bad Request', 400);
    }

    foreach ($events as $event) {
        if (($event['event_type'] ?? '') === 'record_change') {
            $this->processRecordChange($event);
        }
    }

    return response('OK', 200);
}

private function processRecordChange(array $event)
{
    $user = User::find($event['application_user_id'] ?? null);



    $provider = $event['provider_slug'] ?? 'unknown';
    $startDate = substr($event['earliest_record_start_at'] ?? '', 0, 10);
    $endDate = substr($event['latest_record_end_at'] ?? '', 0, 10);

    if (!$startDate || !$endDate) {
        Log::warning("Invalid dates for user {$user->id}, provider {$provider}");
        return;
    }

    $start = new \DateTime($startDate);
    $end = new \DateTime($endDate);

    $metrics = $event['metrics'] ?? [];

    while ($start <= $end) {
        SpikeMetric::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider_slug' => $provider,
                'date' => $start->format('Y-m-d')
            ],
            [
                'steps' => $metrics['steps'] ?? 0,
                'hrv' => $metrics['hrv_rmssd'] ?? null,
                'rhr' => $metrics['heartrate_resting'] ?? null,
                'sleep_hours' => isset($metrics['sleep_duration'])
                    ? round($metrics['sleep_duration'] / (1000 * 60 * 60), 1)
                    : null,
            ]
        );

        $start->modify('+1 day');
    }

    Log::info("Processed Spike webhook record_change for user {$user->id}, provider {$provider}");
}


}
