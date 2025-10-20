<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpikeService
{
    protected $baseUrl;
    protected $appId;
    protected $hmacKey;

    public function __construct()
    {
        $this->baseUrl = env('SPIKE_API_BASE_URL', 'https://app-api.spikeapi.com/v3');
        $this->appId  = env('SPIKE_APPLICATION_ID');
        $this->hmacKey = env('SPIKE_HMAC_KEY');
    }

    // ----------------------------
    // HMAC Authentication
    // ----------------------------
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

        Log::debug('Spike Auth Request Payload', $payload);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/auth/hmac', $payload);

        Log::debug('Spike Auth Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful() && isset($response['access_token'])) {
            return $response['access_token'];
        }

        Log::error('Spike HMAC Auth failed', [
            'status' => $response->status(),
            'body' => $response->body(),
            'userId' => $userId,
            'signature' => $signature
        ]);

        return null;
    }

    // ----------------------------
    // Provider Integration
    // ----------------------------
    public function getProviderIntegrationUrl(string $accessToken, string $provider, ?string $redirectUri = null, ?string $state = null, ?string $providerUserId = null): ?string
    {
        $query = [];

        if ($redirectUri) $query['redirect_uri'] = $redirectUri;
        if ($state) $query['state'] = $state;
        if ($providerUserId) $query['provider_user_id'] = $providerUserId;

        $url = $this->baseUrl . "/providers/{$provider}/integration/init_url";

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






    /**
     * Get user info from Spike
     */
public function getUserInfo($token)
{
    try {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/userinfo"); // <- corrected

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Spike getUserInfo failed', [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body()
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('Spike getUserInfo exception: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}




    /**
     * Get user properties from Spike
     */
    public function getUserProperties($token)
{
    try {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/userproperties"); // corrected endpoint

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Spike getUserProperties failed', [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body() ?? []
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('Spike getUserProperties exception: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

    /**
     * Get provider records
     */
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

            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/queries/provider_records", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spike getProviderRecords failed', $response->json());
            return false;

        } catch (Exception $e) {
            Log::error('Spike getProviderRecords exception: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Get single provider record by ID
     */
public function getProviderRecordById($token, $recordId, $includeSamples = true, $includeProviderSpecificMetrics = true)
{
    try {
        $params = [
            'include_samples' => $includeSamples,
            'include_provider_specific_metrics' => $includeProviderSpecificMetrics,
        ];

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/queries/provider_records/{$recordId}", $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
                'message' => 'Provider record fetched successfully'
            ];
        }

        // Decode JSON response body if possible
        $responseData = $response->json() ?? ['raw' => $response->json()];
        Log::error("Spike getProviderRecordById failed", $responseData);

        return [
            'success' => false,
            'data' => null,
            'message' => 'Failed to fetch provider record',
            'response' => $responseData
        ];

    } catch (\Exception $e) {
        Log::error("Spike getProviderRecordById exception: ".$e->getMessage());

        return [
            'success' => false,
            'data' => null,
            'message' => 'Exception occurred while fetching provider record',
            'error' => $e->getMessage()
        ];
    }
}
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

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/queries/sleeps", $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        Log::error('Spike listSleepData failed', [
            'status' => $response->status(),
            'body' => $response->body(),
            'json' => $response->json() ?? null
        ]);

        return [
            'success' => false,
            'message' => 'Failed to fetch sleep data',
            'status' => $response->status(),
            'raw' => $response->body()
        ];

    } catch (\Exception $e) {
        Log::error('Spike listSleepData exception: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'Exception occurred while fetching sleep data',
            'error' => $e->getMessage()
        ];
    }
}
}
