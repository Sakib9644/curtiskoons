<?php

namespace App\Http\Controllers;

use App\Services\SpikeAuthService;
use App\Services\SpikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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




    public function listProviderRecords(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user || !$user->spike_token) {
                return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
            }

            $fromTimestamp = $request->input('from_timestamp');
            if (!$fromTimestamp) {
                return response()->json(['success' => false, 'message' => 'from_timestamp is required'], 422);
            }

            $records = $this->spikeAuth->getProviderRecords(
                $user->spike_token,
                $fromTimestamp,
                $request->input('to_timestamp', now()->toIso8601String()),
                $request->input('providers', []),
                $request->input('metrics', []),
                $request->input('include_provider_specific_metrics', true)
            );

            if ($records === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch provider records'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Provider records fetched successfully',
                'data' => $records
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    // Get single provider record by ID
    public function getProviderRecord($recordId)
    {
        try {
            $user = auth('api')->user();
            if (!$user || !$user->spike_token) {
                return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
            }

            $record = $this->spikeAuth->getProviderRecordById($user->spike_token, $recordId);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch provider record'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Provider record fetched successfully',
                'data' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    // Get user info
    public function getUserInfo()
{
    try {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
        }

        $info = $this->spikeAuth->getUserInfo($user->spike_token);
        if (!$info) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch user info'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User info fetched successfully',
            'data' => $info
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
    }
}


    // Get user properties
 public function getUserProperties()
{
    try {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
        }

        $properties = $this->spikeAuth->getUserProperties($user->spike_token);
        if (!$properties) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch user properties'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User properties fetched successfully',
            'data' => $properties
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ], 500);
    }
}


    // List user sleep data
    public function listSleep(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        if (!$fromDate || !$toDate) {
            return response()->json(['success' => false, 'message' => 'from_date and to_date are required'], 422);
        }

        $providers = $request->input('providers', []);
        $includeStages = $request->boolean('include_stages', true);
        $includeSamples = $request->boolean('include_samples', true);

        $result = $this->spikeAuth->listSleepData($user->spike_token, $fromDate, $toDate, $providers, $includeStages, $includeSamples);

        return response()->json($result);
    }
}
