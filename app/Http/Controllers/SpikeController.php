<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SpikeAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SpikeController extends Controller
{
    protected $spike;

    public function __construct(SpikeAuthService $spike)
    {
        $this->spike = $spike; // SpikeAuthService injected
    }

    /**
     * Step 1: Authenticate user with Spike and get Spike access token
     */
    public function authenticateUser(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User not logged in'], 401);
        }

        $userId = (string) $user->id;
        $token = $this->spike->getAccessToken($userId);

        if (!$token) {
            return response()->json(['error' => 'Failed to authenticate user'], 401);
        }

        // Save Spike token in users table
        $user->spike_token = $token;
        $user->save();

        return response()->json([
            'user_id' => $userId,
            'access_token' => $token
        ]);
    }

    /**
     * Step 2: Generate provider integration URL
     * API returns the URL for the client to open in browser or WebView
     */
    public function integrateProvider(Request $request, $provider)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['error' => 'User not authenticated with Spike'], 401);
        }

        if (!$provider) {
            return response()->json(['error' => 'Provider slug is required'], 400);
        }

        $redirectUri = $request->input('redirect_uri'); // optional
        $state = $request->input('state');             // optional

        $integrationUrl = $this->spike->getProviderIntegrationUrl(
            $user->spike_token,
            $provider,
            $redirectUri,
            $state
        );

        if (!$integrationUrl) {
            return response()->json(['error' => 'Failed to get integration URL'], 500);
        }

        return response()->json([
            'provider' => $provider,
            'integration_url' => $integrationUrl
        ]);
    }

    /**
     * Step 3: Handle callback from provider after user authorizes
     * Stores provider connection in user_providers table
     */
    public function providerCallback(Request $request)
    {
        $userId = $request->query('user_id');
        $provider = $request->query('provider_slug');
        $code = $request->query('code');  // sometimes provided
        $state = $request->query('state');

        if (!$userId || !$provider) {
            return response()->json(['error' => 'Missing user_id or provider_slug'], 400);
        }

        $user = \App\Models\User::find($userId);
        if (!$user || !$user->spike_token) {
            return response()->json(['error' => 'User not authenticated with Spike'], 401);
        }

        // Confirm provider connection with Spike
        $result = $this->spike->confirmProviderConnection(
            $user->spike_token,
            $provider,
            $code,
            $state
        );

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
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

        return response()->json([
            'message' => 'Provider connected successfully',
            'provider' => $provider,
            'data' => $result
        ]);
    }
}
