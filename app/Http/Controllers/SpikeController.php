<?php

namespace App\Http\Controllers;

use App\Services\SpikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpikeController extends Controller
{
    protected $spikeService;

    public function __construct(SpikeService $spikeService)
    {
        $this->spikeService = $spikeService;
    }

    /**
     * Authenticate logged-in user and get Spike access token
     */
    public function authenticateUser(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User not logged in'], 401);
        }

        $userId = (string) $user->id;
        $token = $this->spikeService->getAccessToken($userId);

        if (!$token) {
            return response()->json(['error' => 'Failed to authenticate user'], 401);
        }

        // Save the token in the existing column
        $user->spike_token = $token;
        $user->save();

        return response()->json([
            'user_id' => $userId,
            'access_token' => $token
        ]);
    }

    public function integrateProvider(Request $request, $provider)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['error' => 'User not authenticated with Spike'], 401);
        }

        if (!$provider) {
            return response()->json(['error' => 'Provider slug is required'], 400);
        }

        // Validate required inputs
        try {
            $request->validate([
                'redirect_uri' => 'required|url',
                'state' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $redirectUri = $request->input('redirect_uri');
        $state = $request->input('state');

        $integrationUrl = $this->spikeService->getProviderIntegrationUrl(
            $user->spike_token,
            $provider,
            $redirectUri,
            $state
        );

        if (!$integrationUrl) {
            return response()->json(['error' => 'Failed to get integration URL'], 500);
        }

        // Return the URL in JSON for API clients
        return response()->json([
            'provider' => $provider,
            'integration_url' => $integrationUrl
        ]);
    }

    public function providerCallback(Request $request)
    {
        $userId = $request->query('user_id');
        $provider = $request->query('provider_slug');
        $code = $request->query('code');  // sometimes provided
        $state = $request->query('state');

        // Log incoming request for debugging
        \Illuminate\Support\Facades\Log::info('Provider Callback Hit', $request->query());

        if (!$userId || !$provider) {
            return response()->json(['error' => 'Missing user_id or provider_slug'], 400);
        }

        if (!$code) {
            return response()->json(['error' => 'Missing OAuth code from provider'], 400);
        }

        $user = \App\Models\User::find($userId);
        if (!$user || !$user->spike_token) {
            return response()->json(['error' => 'User not authenticated with Spike'], 401);
        }

        // Confirm provider connection with Spike
        $result = $this->spikeService->confirmProviderConnection(
            $user->spike_token,
            $provider,
            $code,
            $state
        );

        if (isset($result['error'])) {
            return response()->json([
                'error' => $result['error'],
                'debug' => app()->environment('local') ? $result['body'] ?? null : null
            ], 400);
        }

        // Save provider in database
        DB::table('user_providers')->updateOrInsert(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'provider_user_id' => $result['provider_user_id'] ?? null,
                'access_token' => $result['access_token'] ?? null,
                'updated_at' => now()
            ]
        );

        $successUrl = $request->query('success_url') ?? '/dashboard';
        $redirectUrl = $successUrl . '?provider=' . urlencode($provider) . '&success=1';

        // For web/OAuth, prefer redirect; fallback to JSON for API
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Provider connected successfully',
                'provider' => $provider,
                'data' => $result
            ]);
        }

        return redirect($redirectUrl);
    }
}
