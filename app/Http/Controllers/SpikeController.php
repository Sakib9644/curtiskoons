<?php

namespace App\Http\Controllers;

use App\Services\SpikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $user->spike_token = $token;
        $user->save();

        return response()->json([
            'user_id' => $userId,
            'access_token' => $token
        ]);
    }

    /**
     * Integrate provider (Fitbit, Garmin, etc.)
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

        $redirectUri = $request->input('redirect_uri');
        $state = $request->input('state');

        $integrationUrl = $this->spikeAuth->getProviderIntegrationUrl(
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
     * List provider records
     */
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
                return response()->json(['success' => false, 'message' => 'Failed to fetch provider records'], 500);
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

    /**
     * Get single provider record by ID
     */
    public function getProviderRecord($recordId)
    {
        try {
            $user = auth('api')->user();
            if (!$user || !$user->spike_token) {
                return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
            }

            $record = $this->spikeAuth->getProviderRecordById($user->spike_token, $recordId);

            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch provider record'], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Provider record fetched successfully',
                'data' => $record['data']
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get Spike user info
     */
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

    /**
     * Get Spike user properties
     */
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
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List user sleep data
     */
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

        $result = $this->spikeAuth->listSleepData(
            $user->spike_token,
            $fromDate,
            $toDate,
            $providers,
            $includeStages,
            $includeSamples
        );

        return response()->json($result);
    }

    /**
     * Get single sleep record by ID
     */
    public function getSleepRecord(Request $request, $sleepId)
    {
        try {
            $user = auth('api')->user();
            if (!$user || !$user->spike_token) {
                return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
            }

            $includeStages = $request->boolean('include_stages', true);
            $includeSamples = $request->boolean('include_samples', true);

            $sleepData = $this->spikeAuth->getSleepRecord(
                $user->spike_token,
                $sleepId,
                $includeStages,
                $includeSamples
            );

            if (!$sleepData) {
                return response()->json(['success' => false, 'message' => 'No Sleep Id Found'], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sleep record fetched successfully',
                'data' => $sleepData
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🏋️‍♂️ List workouts data
     */


    /**
     * 🏋️‍♀️ Get single workout record by ID
     */
    public function listWorkouts(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
        }

        $fromTimestamp = $request->input('from_timestamp');
        $toTimestamp = $request->input('to_timestamp', now()->toIso8601String());

        if (!$fromTimestamp) {
            return response()->json(['success' => false, 'message' => 'from_timestamp is required'], 422);
        }

        $result = $this->spikeAuth->listWorkouts($user->spike_token, $fromTimestamp, $toTimestamp);

        return response()->json($result);
    }

    public function getWorkoutById($workoutId)
    {
        try {
            $user = auth('api')->user();
            if (!$user || !$user->spike_token) {
                return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
            }

            $workout = $this->spikeAuth->getWorkoutById($user->spike_token, $workoutId);

            if (!$workout) {
                return response()->json(['success' => false, 'message' => 'Workout not found or failed to fetch'], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Workout fetched successfully',
                'data' => $workout
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching workout: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
        }
    }
    public function getIntervalStatistics(Request $request)
{
    $user = auth('api')->user();
    if (!$user || !$user->spike_token) {
        return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
    }

    $params = $request->only([
        'from_timestamp',
        'to_timestamp',
        'interval',
        'types',
        'include_record_ids'
    ]);

    if (!isset($params['types']) || !is_array($params['types']) || empty($params['types'])) {
        return response()->json(['success' => false, 'message' => 'types[] query parameter is required'], 422);
    }

    $data = $this->spikeAuth->getIntervalStatistics($user->spike_token, $params);

    if (!$data) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch interval statistics'
        ], 500);
    }

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}

}
