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


}




