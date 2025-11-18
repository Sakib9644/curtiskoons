<?php

namespace App\Http\Controllers;

use App\Models\SpikeMetric;
use App\Models\User;
use Illuminate\Http\Request;

class SpikeWebhookController extends Controller
{
    // Read HMAC key from env
    private $hmacKey;

    public function __construct()
    {
        $this->hmacKey = env('SPIKE_HMAC_KEY');
    }

    public function handle(Request $request)
    {
        $signature = $request->header('X-Body-Signature');
        $body = $request->getContent();
        $calculatedSignature = hash_hmac('sha256', $body, $this->hmacKey);

        Log::info('webhook recived
        ');
        if ($signature !== $calculatedSignature) {
            return response('Unauthorized', 401);
        }

        $events = json_decode($body, true);
        if (!$events) {
            Log::error('Spike webhook invalid JSON');
            return response('Bad Request', 400);
        }

        foreach ($events as $event) {
            if ($event['event_type'] === 'record_change') {
                $this->processRecordChange($event);
            }
        }

        return response('OK', 200);
    }

    private function processRecordChange(array $event)
    {
        $user = User::find($event['application_user_id']);
        if (!$user) {
            Log::warning("User {$event['application_user_id']} not found");
            return;
        }

        $provider = $event['provider_slug'];
        $startDate = substr($event['earliest_record_start_at'], 0, 10);
        $endDate = substr($event['latest_record_end_at'], 0, 10);

        $currentDate = $startDate;
        while ($currentDate <= $endDate) {

            $metrics = $event['metrics'] ?? [];

            // Save/update in DB
            SpikeMetric::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider_slug' => $provider,
                    'date' => $currentDate
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

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        Log::info("Processed Spike webhook record_change for user {$user->id}, provider {$provider}");
    }
}
