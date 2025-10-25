<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SpikeService
{
    protected $baseUrl;
    protected $appId;
    protected $hmacKey;

    public function __construct()
    {
        $this->baseUrl = env('SPIKE_API_BASE_URL', 'https://app-api.spikeapi.com/v3');
        $this->appId   = env('SPIKE_APPLICATION_ID');
        $this->hmacKey = env('SPIKE_HMAC_KEY');
    }

    // ------------------------------------------------
    // 🔐 HMAC Authentication
    // ------------------------------------------------
    public function generateHmacSignature(string $userId): string
    {
        $userId = trim($userId);
        $hmac = hash_hmac('sha256', $userId, $this->hmacKey, false);

        Log::debug('Generated HMAC Signature', [
            'userId' => $userId,
            'hmac' => $hmac
        ]);

        return $hmac;
    }

    public function getAccessToken(string $userId): ?string
    {
        $signature = $this->generateHmacSignature($userId);

        $payload = [
            'application_id' => (int)$this->appId,
            'application_user_id' => $userId,
            'signature' => $signature
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/auth/hmac", $payload);

        Log::debug('Spike Auth Response', [
            'status' => $response->status(),
            'body'   => $response->body()
        ]);

        if ($response->successful() && isset($response['access_token'])) {
            return $response['access_token'];
        }

        Log::error('Spike HMAC Auth failed', [
            'status' => $response->status(),
            'body'   => $response->body(),
            'userId' => $userId,
            'signature' => $signature
        ]);

        return null;
    }

    // ------------------------------------------------
    // 🌐 Provider Integration
    // ------------------------------------------------
    public function getProviderIntegrationUrl(
        string $accessToken,
        string $provider,
        ?string $redirectUri = null,
        ?string $state = null,
        ?string $providerUserId = null
    ): ?string {
        $query = [];

        if ($redirectUri) $query['redirect_uri'] = $redirectUri;
        if ($state) $query['state'] = $state;
        if ($providerUserId) $query['provider_user_id'] = $providerUserId;

        $url = "{$this->baseUrl}/providers/{$provider}/integration/init_url";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ])->get($url, $query);

        Log::debug('Spike Provider Integration URL Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful() && isset($response['path'])) {
            return $response['path'];
        }

        return null;
    }

    // ------------------------------------------------
    // 👤 User Info
    // ------------------------------------------------
    public function getUserInfo($token)
    {
        try {
            $response = Http::withToken($token)->get("{$this->baseUrl}/userinfo");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spike getUserInfo failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body()
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Spike getUserInfo exception: ' . $e->getMessage());
            return null;
        }
    }

    // ------------------------------------------------
    // 🧩 User Properties
    // ------------------------------------------------
    public function getUserProperties($token)
    {
        try {
            $response = Http::withToken($token)->get("{$this->baseUrl}/userproperties");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spike getUserProperties failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body()
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Spike getUserProperties exception: ' . $e->getMessage());
            return null;
        }
    }

    // ------------------------------------------------
    // 📊 Provider Records
    // ------------------------------------------------
    public function getProviderRecords($token, $fromTimestamp, $toTimestamp, $providers = [], $metrics = [], $includeProviderSpecificMetrics = true)
    {
        try {
            $params = [
                'from_timestamp' => $fromTimestamp,
                'to_timestamp' => $toTimestamp,
                'providers' => $providers,
                'metrics' => $metrics,
                'include_provider_specific_metrics' => $includeProviderSpecificMetrics,
            ];

            $response = Http::withToken($token)->get("{$this->baseUrl}/queries/provider_records", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spike getProviderRecords failed', $response->json());
            return false;
        } catch (Exception $e) {
            Log::error('Spike getProviderRecords exception: ' . $e->getMessage());
            return false;
        }
    }

    public function getProviderRecordById($token, $recordId, $includeSamples = true, $includeProviderSpecificMetrics = true)
    {
        try {
            $params = [
                'include_samples' => $includeSamples,
                'include_provider_specific_metrics' => $includeProviderSpecificMetrics,
            ];

            $response = Http::withToken($token)->get("{$this->baseUrl}/queries/provider_records/{$recordId}", $params);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error("Spike getProviderRecordById failed", ['response' => $response->json()]);
            return ['success' => false, 'message' => 'Failed to fetch provider record'];
        } catch (Exception $e) {
            Log::error("Spike getProviderRecordById exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ------------------------------------------------
    // 💤 Sleep Data
    // ------------------------------------------------
    public function listSleepData($token, $fromDate, $toDate, $providers = [], $includeStages = true, $includeSamples = true)
    {
        try {
            $params = [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'providers' => $providers,
                'include_stages' => $includeStages,
                'include_samples' => $includeSamples,
            ];

            $response = Http::withToken($token)->get("{$this->baseUrl}/queries/sleeps", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Sleep data retrieved successfully',
                    'data' => $response->json(),
                ];
            }

            Log::error('Spike listSleepData failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'Failed to fetch sleep data'];
        } catch (Exception $e) {
            Log::error('Spike listSleepData exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getSleepRecord($token, $sleepId, $includeStages = true, $includeSamples = true)
    {
        try {
            $query = [
                'include_stages' => $includeStages ? 'true' : 'false',
                'include_samples' => $includeSamples ? 'true' : 'false',
            ];

            $response = Http::withToken($token)->get("{$this->baseUrl}/sleep/{$sleepId}", $query);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spike getSleepRecord failed', ['response' => $response->json()]);
            return null;
        } catch (Exception $e) {
            Log::error('Spike getSleepRecord exception: ' . $e->getMessage());
            return null;
        }
    }

    // ------------------------------------------------
    // 💪 Workouts
    // ------------------------------------------------

    public function listWorkouts($token, $fromTimestamp, $toTimestamp)
    {
        try {
            $params = [
                'from_timestamp' => $fromTimestamp,
                'to_timestamp' => $toTimestamp,
            ];

            $response = Http::withToken($token)->get("{$this->baseUrl}/queries/workouts", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Workouts fetched successfully',
                    'data' => $response->json()
                ];
            }

            Log::error('Spike listWorkouts failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch workouts',
                'status' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('Spike listWorkouts exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getWorkoutById($token, $workoutId)
    {
        try {
            $url = "{$this->baseUrl}/queries/workouts/{$workoutId}";

            $response = Http::withToken($token)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spike getWorkoutById failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Spike getWorkoutById exception: ' . $e->getMessage());
            return null;
        }
    }


public function getIntervalStatistics(string $token, array $params)
{
    $defaultParams = [
        'from_timestamp' => now()->subDay()->toIso8601String(),
        'to_timestamp' => now()->toIso8601String(),
        'interval' => '1h',
        'types' => ['steps'], // default type
        'include_record_ids' => false,
    ];

    $params = array_merge($defaultParams, $params);

    // Build the query string without array notation for 'types'
    $query = [];
    foreach ($params as $key => $value) {
        if ($key === 'types' && is_array($value)) {
            foreach ($value as $type) {
                $query[] = "types=" . urlencode($type);
            }
        } else {
            $query[] = urlencode($key) . "=" . urlencode($value);
        }
    }

    $queryString = implode('&', $query);

    try {
        $url = "{$this->baseUrl}/queries/statistics/interval?" . $queryString;

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($url);

        if ($response->failed()) {
            Log::error('Spike getIntervalStatistics failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
            ]);
            return null;
        }

        return $response->json();
    } catch (\Exception $e) {
        Log::error('Spike getIntervalStatistics exception', [
            'message' => $e->getMessage(),
        ]);
        return null;
    }
}

}
