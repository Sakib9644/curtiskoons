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

        $json = $response->json();  // Decode JSON body

        Log::debug('Spike Auth Response', [
            'status' => $response->status(),
            'body' => $json
        ]);

        if ($response->successful() && isset($json['access_token'])) {
            return $json['access_token'];
        }

        Log::error('Spike HMAC Auth failed', [
            'status' => $response->status(),
            'body' => $json,
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

        $json = $response->json();  // Decode JSON body

        Log::debug('Spike Provider Integration URL Response', [
            'status' => $response->status(),
            'body' => $json
        ]);

        if ($response->successful() && isset($json['path'])) {
            return $json['path'];
        }

        return null;
    }

    public function confirmProviderConnection(string $accessToken, string $provider, ?string $code = null, ?string $state = null): array
    {
        $payload = [];
        if ($code) $payload['code'] = $code;
        if ($state) $payload['state'] = $state;

        $url = $this->baseUrl . "/providers/{$provider}/integration/confirm";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ])->post($url, $payload);

        $json = $response->json();  // Decode for consistency, though not strictly needed here

        Log::debug('Spike Confirm Provider Response', [
            'status' => $response->status(),
            'body' => $json
        ]);

        if ($response->successful()) {
            return $json;
        }

        return [
            'error' => 'Failed to confirm provider connection',
            'status' => $response->status(),
            'body' => $json
        ];
    }
}
