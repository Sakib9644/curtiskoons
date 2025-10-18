<?php

namespace App\Http\Controllers;

use App\Services\SpikeAuthService;
use App\Services\SpikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpikeController extends Controller
{
    protected $spikeAuth;

    public function __construct(SpikeService $spikeAuth)
    {
        $this->spikeAuth = $spikeAuth;
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
    $token = $this->spikeAuth->getAccessToken($userId);

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

    $redirectUri = $request->input('redirect_uri'); // optional
    $state = $request->input('state');             // optional

    $integrationUrl = $this->spikeAuth->getProviderIntegrationUrl(
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

    if (!$userId || !$provider) {
        return response()->json(['error' => 'Missing user_id or provider_slug'], 400);
    }

    $user = \App\Models\User::find($userId);
    if (!$user || !$user->spike_token) {
        return response()->json(['error' => 'User not authenticated with Spike'], 401);
    }

    // Retrieve the integration code stored when generating the URL
    $integrationCode = cache()->pull("spike_integration_code_{$userId}_{$provider}");
    if (!$integrationCode) {
        return response()->json(['error' => 'Integration code missing or expired'], 400);
    }

    $result = $this->spikeAuth->confirmProviderConnection(
        $user->spike_token,
        $provider,
        $integrationCode
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




