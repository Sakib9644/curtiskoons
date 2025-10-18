<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;

class SpikeService
{
    public $appId;
    public $hmacSecret;
    public $baseUrl;

    public function __construct()
    {
        $this->appId = config('services.spike.app_id');
        $this->hmacSecret = config('services.spike.hmac_secret');
        $this->baseUrl = config('services.spike.base_url');
    }

    /**
     * Generate HMAC-SHA256 signature for a user.
     */
    public function generateSignature(string $userId): string
    {
        return hash_hmac('sha256', $userId, $this->hmacSecret);
    }

    /**
     * Authenticate user and get/store access token.
     * Call this when a user first connects (e.g., in a controller after provider consent).
     */
    public function authenticateUser(User $user): string
    {
        $applicationUserId = $this->getApplicationUserId($user);
        $signature = $this->generateSignature($applicationUserId);

        $response = Http::asJson()
            ->post("{$this->baseUrl}/auth/hmac", [
                'application_id' => $this->appId,
                'application_user_id' => $applicationUserId,
                'signature' => $signature,
            ]);

        if ($response->failed()) {
            Log::error('Spike auth failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $user->id
            ]);
            throw new Exception('Failed to authenticate with Spike: ' . $response->body());
        }

        $data = $response->json();
        $accessToken = $data['access_token'];

        // Store token in DB
        $user->update(['spike_token' => $accessToken]);

        return $accessToken;
    }

    /**
     * Get stored access token for a user.
     */
    public function getAccessToken(User $user): ?string
    {
        return $user->spike_token;
    }

    /**
     * Make a GET request to Spike API (e.g., fetch daily stats).
     * Handles token attachment.
     */
    public function get(string $endpoint, array $params = [], User $user = null): array
    {
        $token = $user ? $this->getAccessToken($user) : null;
        if (!$token) {
            throw new Exception('No access token available. Authenticate user first.');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/{$endpoint}", $params);

        if ($response->failed()) {
            Log::error('Spike API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('Spike API error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch daily stats for a user, optionally from a specific provider.
     */
    public function getDailyStats(User $user, string $startDate, string $endDate, ?string $provider = null): array
    {
        $params = [
            'start_date' => $startDate,  // YYYY-MM-DD
            'end_date' => $endDate,
        ];

        if ($provider) {
            $params['provider'] = $provider;  // e.g., 'garmin', 'fitbit'
        }

        return $this->get("users/{$this->getApplicationUserId($user)}/daily", $params, $user);
    }

    /**
     * Fetch workouts for a user, optionally from a specific provider.
     */
    public function getWorkouts(User $user, ?string $provider = null, int $limit = 10): array
    {
        $params = ['limit' => $limit];
        if ($provider) {
            $params['provider'] = $provider;
        }
        return $this->get("users/{$this->getApplicationUserId($user)}/workouts", $params, $user);
    }

    /**
     * Helper: Generate consistent application_user_id (e.g., prefix your Laravel user ID).
     * Made public for external calls (e.g., from controllers).
     */
    public function getApplicationUserId(User $user): string
    {
        return 'laravel_user_' . $user->id;  // Max 128 chars, alphanumeric + -/_ allowed
    }

    /**
     * Getter for base URL (public for external access, e.g., building OAuth URLs).
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Getter for app ID (public for external access).
     */
    public function getAppId(): string
    {
        return $this->appId;
    }
}
