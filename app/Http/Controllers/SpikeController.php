<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProviders;
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

    public function connection(Request $request)
    {
        $providerSlug = $request->input('provider_slug');
        $userId = $request->input('user_id');

        if (!$providerSlug || !$userId) {
            return response()->json(['error' => 'Missing provider_slug or user_id'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($providerSlug) . " Connected Successfully",
        ]);
    }

    /**
     */
    private function getRepeatedQueryParam(Request $request, string $key): array
    {
        $queryString = $request->getQueryString();
        if (!$queryString) {
            return [];
        }

        $pattern = '/' . preg_quote($key . '=', '/') . '([^&]*)/';
        preg_match_all($pattern, $queryString, $matches);
        $values = $matches[1] ?? [];
        return array_map('urldecode', $values);
    }

    /**
     * Authenticate logged-in user and get Spike access token
     */
    public function authenticateUser()
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
        $userProvider = $user->provider();


        if (in_array($provider, ['oura', 'whoop', 'garmin'])) {

            $existingProvider = $userProvider
                ->whereIn('provider', ['oura', 'whoop', 'garmin'])
                ->first();

            if ($existingProvider) {
                return response()->json([
                    'status' => false,
                    'message' => "You cannot add more than one provider among Oura, Whoop, and Garmin. You already connected: {$existingProvider->provider}.",
                ], 403);
            }
        }





        if (!$user) {
            Log::warning('Provider integration failed - User not logged in');
            return response()->json(['error' => 'User not logged in'], 401);
        }

        Log::info('Starting provider integration', [
            'user_id' => $user->id,
            'provider' => $provider,
            'has_spike_token' => !empty($user->spike_token)
        ]);

        // Get or refresh Spike token if not present
        if (!$user->spike_token) {
            Log::info('No existing Spike token, fetching new one', [
                'user_id' => $user->id
            ]);

            $userId = (string) $user->id;
            $token = $this->spikeAuth->getAccessToken($userId);

            if (!$token) {
                Log::error('Failed to get Spike access token', [
                    'user_id' => $userId
                ]);
                return response()->json(['error' => 'Failed to authenticate with Spike'], 401);
            }

            Log::info('Successfully obtained Spike token', [
                'user_id' => $userId,
                'token_length' => strlen($token)
            ]);

            $user->spike_token = $token;
            $user->save();

            Log::info('Spike token saved to user', [
                'user_id' => $userId
            ]);
        }

        // Validate provider
        if (!$provider) {
            Log::error('Provider slug missing in request');
            return response()->json(['error' => 'Provider slug is required'], 400);
        }

        // Get integration URL
        $redirectUri = $request->input('redirect_uri');
        $state = $request->input('state');

        Log::info('Requesting provider integration URL', [
            'user_id' => $user->id,
            'provider' => $provider,
            'redirect_uri' => $redirectUri,
            'state' => $state
        ]);

        $integrationUrl = $this->spikeAuth->getProviderIntegrationUrl(
            $user->spike_token,
            $provider,
            $redirectUri,
            $state
        );

        if (!$integrationUrl) {
            Log::error('Failed to get provider integration URL', [
                'user_id' => $user->id,
                'provider' => $provider,
                'has_token' => !empty($user->spike_token),
                'redirect_uri' => $redirectUri
            ]);
            return response()->json(['error' => 'Failed to get integration URL'], 500);
        }

        Log::info('Successfully generated integration URL', [
            'user_id' => $user->id,
            'provider' => $provider,
            'url' => $integrationUrl
        ]);

        return response()->json([
            'provider' => $provider,
            'integration_url' => $integrationUrl
        ]);
    }
    public function providerCallback(Request $request)
{
    $id     = $request->input('user_id');             // User ID from request
    $slug   = $request->input('provider_slug');      // Provider slug from request

    $user = User::find($id);
    if (!$user) {
        return response()->json([
            'status'  => false,
            'message' => 'User not found'
        ], 404);
    }

    $providerUserId = $id;
    $accessToken    = $user->spike_token;


    $provider = UserProviders::updateOrCreate(
        [
            'user_id'  => $id,       // search by user
            'provider' => $slug      // search by provider
        ],
        [
            'provider_user_id' => $providerUserId,  // value to update/save
            'access_token'     => $accessToken      // value to update/save
        ]
    );

    return response()->json([
        'status'  => true,
        'message' => "Provider {$slug} saved successfully!",
        'data'    => $provider
    ]);
}


    public function connectedusers()
    {

        $user = auth('api')->user();

        $providers = $user?->provider()->select('provider')->get();

        return response()->json([
            'status' => true,
            'message' => "Provider retrive successfully",
            'data' =>   $providers
        ]);
    }
public function listProviderRecords(Request $request)
{
    try {
        $user = auth('api')->user();
        

        $fromTimestamp = $request->input('from_timestamp');
        if (!$fromTimestamp) {
            return response()->json(['success' => false, 'message' => 'from_timestamp is required'], 422);
        }

        $records = $this->spikeAuth->getProviderRecords(
            $user->spike_token,
            $fromTimestamp,
            $request->input('to_timestamp', now()->toIso8601String()),
            $this->getRepeatedQueryParam($request, 'providers'),
            $this->getRepeatedQueryParam($request, 'metrics'),
            $request->boolean('include_provider_specific_metrics', true)
        );

        $rawRecords = $records['records'] ?? [];

        // Group records by date
        $recordsByDate = [];
        foreach ($rawRecords as $record) {
            $date = $record['provider_local_date'] ?? null;
            if ($date) {
                $recordsByDate[$date][] = $record;
            }
        }

        // Sort dates ascending
        $dates = array_keys($recordsByDate);
        sort($dates);

        // Find the most recent date with valid metrics (today or previous days)
        $selectedDate = null;
        foreach (array_reverse($dates) as $date) { // newest first
            foreach ($recordsByDate[$date] as $record) {
                $metrics = $record['metrics'] ?? [];
                foreach (['hrv_rmssd', 'heartrate_resting', 'sleep_duration', 'steps'] as $key) {
                    if (!empty($metrics[$key]) && $metrics[$key] != 0) {
                        $selectedDate = $date;
                        break 3; // found valid metrics, stop all loops
                    }
                }
            }
        }

        // Initialize summary with default values (always)
        $summary = [
            'date' => $selectedDate,
            'HRV' => null,
            'HRV_status' => 'N/A',
            'RHR' => null,
            'RHR_status' => 'N/A',
            'Sleep_hours' => null,
            'Sleep_status' => 'N/A',
            'Steps' => 0,
            'Steps_status' => 'N/A',
            'provider_slug' => null,
        ];

        // Only populate summary if selectedDate exists
        if ($selectedDate) {
            $steps = 0;

            foreach ($recordsByDate[$selectedDate] as $record) {
                $metrics = $record['metrics'] ?? [];

                if (!$summary['provider_slug'] && isset($record['provider_slug'])) {
                    $summary['provider_slug'] = $record['provider_slug'];
                }

                // HRV
                if (!empty($metrics['hrv_rmssd'])) {
                    $summary['HRV'] = $metrics['hrv_rmssd'];
                    $hrv = $metrics['hrv_rmssd'];
                    $summary['HRV_status'] = $hrv < 30 ? 'Poor' : ($hrv <= 60 ? 'Good' : 'Excellent');
                }

                // RHR
                if (!empty($metrics['heartrate_resting'])) {
                    $summary['RHR'] = $metrics['heartrate_resting'];
                    $rhr = $metrics['heartrate_resting'];
                    $summary['RHR_status'] = $rhr < 55 ? 'Excellent' : ($rhr <= 65 ? 'Good' : 'Poor');
                }

                // Sleep
                if (!empty($metrics['sleep_duration'])) {
                    $hours = $metrics['sleep_duration'] / (1000 * 60 * 60);
                    $summary['Sleep_hours'] = round($hours, 1);
                    if ($hours < 6) $summary['Sleep_status'] = 'Poor';
                    elseif ($hours < 8) $summary['Sleep_status'] = 'Good';
                    elseif ($hours < 9) $summary['Sleep_status'] = 'Optimal';
                    else $summary['Sleep_status'] = 'Excellent';
                }

                // Steps
                if (!empty($metrics['steps'])) {
                    $steps += $metrics['steps'];
                }
            }

            $summary['Steps'] = $steps;
            $summary['Steps_status'] = $steps < 5000 ? 'Poor' : ($steps < 8000 ? 'Good' : ($steps < 12000 ? 'Optimal' : 'Excellent'));
        }

        // Always return the summary object
        return response()->json([
            'success' => true,
            'message' => $selectedDate ? 'Summary fetched successfully' : 'No valid data available, default summary returned',
            'data' => $summary
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ], 500);
    }
}
function calculateBluegrassAge(array $data)
{
    // -------------------------------
    // 1. Required Inputs
    // -------------------------------
    $age  = $data['age'] ?? 0;
    $sex  = $data['sex'] ?? 'male';

    // Body measurements
    $height = $data['height'] ?? 0;     // cm
    $waist  = $data['waist'] ?? 0;      // cm

    // Labs
    $labs = $data['labs'] ?? [];

    // Fitness data
    $fitness = $data['fitness'] ?? [];

    // Lifestyle
    $life = $data['lifestyle'] ?? [];

    // -------------------------------
    // 2. Lab Weights
    // -------------------------------
    $weights = [
        'albumin' => -1.2,
        'creatinine' => 0.6,
        'glucose' => 0.9,
        'hscrp' => 1,
        'lymph' => -0.6,
        'mcv' => 0.4,
        'rdw' => 1.2,
        'alkphos' => 0.5,
        'wbc' => 0.8,
    ];

    $lab_scale_to_years = 2;

    // -------------------------------
    // 3. Fitness Weights
    // -------------------------------
    $vo2_per_10pct = -0.8;
    $hrv_per_10pct = -0.3;
    $rhr_high_penalty = 0.8;
    $rhr_low_bonus    = -0.8;

    // -------------------------------
    // 4. Waist:Height Ratio Logic
    // -------------------------------
    $whtr = ($height > 0) ? $waist / $height : 0;

    if ($whtr > 0.55) {
        $whtr_score = 1;
    } elseif ($whtr >= 0.50) {
        $whtr_score = 0.5;
    } else {
        $whtr_score = -0.5;
    }

    // -------------------------------
    // 5. Lifestyle Weights
    // -------------------------------
    $sleep_ge7   = -0.5;
    $sleep_lt6   = 0.5;
    $activity_met = -0.5;
    $activity_sedentary = 0.5;
    $diet_high = -0.5;
    $diet_low  = 0.5;
    $alcohol_3_14 = 0.25;
    $alcohol_gt14 = 1;
    $nicotine_current = 1.5;
    $nicotine_former  = 0.5;
    $sunburns_ge2     = 0.25;
    $glp1_good = -0.25;
    $crash_diet = 0.5;

    // -------------------------------
    // 6. Compute Lab Age
    // -------------------------------
    $lab_age_delta = 0;

    foreach ($weights as $key => $w) {
        if (!isset($labs[$key])) continue;
        $lab_age_delta += $labs[$key] * $w;
    }

    $core_lab_age = $age + ($lab_age_delta * $lab_scale_to_years);

    // -------------------------------
    // 7. Fitness Adjustments
    // -------------------------------
    $fitness_adj = 0;

    // VO2max ratio
    if (isset($fitness['vo2max'], $fitness['expected_vo2'])) {
        $ratio = $fitness['vo2max'] / $fitness['expected_vo2'];
        $fitness_adj += (($ratio - 1) * 10) * $vo2_per_10pct;
    }

    // HRV ratio
    if (isset($fitness['hrv'], $fitness['expected_hrv'])) {
        $ratio = $fitness['hrv'] / $fitness['expected_hrv'];
        $fitness_adj += (($ratio - 1) * 10) * $hrv_per_10pct;
    }

    // Resting heart rate bonus/penalty
    if (isset($fitness['rhr'])) {
        if ($fitness['rhr'] > 75) $fitness_adj += $rhr_high_penalty;
        if ($fitness['rhr'] < 55) $fitness_adj += $rhr_low_bonus;
    }

    // Add WHtR contribution
    $fitness_adj += $whtr_score;

    // -------------------------------
    // 8. Lifestyle Adjustments
    // -------------------------------
    $life_adj = 0;

    // Sleep
    if (!empty($life['sleep_hours'])) {
        if ($life['sleep_hours'] >= 7) $life_adj += $sleep_ge7;
        if ($life['sleep_hours'] < 6)  $life_adj += $sleep_lt6;
    }

    // Activity levels
    if (!empty($life['activity_met'])) {
        $life_adj += $activity_met;
    } elseif (!empty($life['activity_low'])) {
        $life_adj += $activity_sedentary;
    }

    // Diet
    if (!empty($life['diet_high'])) $life_adj += $diet_high;
    if (!empty($life['diet_low']))  $life_adj += $diet_low;

    // Alcohol
    if (!empty($life['alcohol_3_14'])) $life_adj += $alcohol_3_14;
    if (!empty($life['alcohol_gt14'])) $life_adj += $alcohol_gt14;

    // Nicotine
    if (!empty($life['nicotine_current'])) $life_adj += $nicotine_current;
    if (!empty($life['nicotine_former']))  $life_adj += $nicotine_former;

    // Sunburns
    if (!empty($life['sunburns_ge2'])) $life_adj += $sunburns_ge2;

    // GLP-1 (safe wellness setting)
    if (!empty($life['glp1_good'])) $life_adj += $glp1_good;

    // Crash dieting (flag only)
    if (!empty($life['crash_diet'])) $life_adj += $crash_diet;

    // -------------------------------
    // 9. Final Bluegrass Age
    // -------------------------------
    $bluegrass_age = $core_lab_age + $fitness_adj + $life_adj;

    return [
        'bluegrass_age' => round($bluegrass_age, 1),
        'core_lab_age'  => round($core_lab_age, 1),
        'fitness_adj'   => round($fitness_adj, 1),
        'lifestyle_adj' => round($life_adj, 1),
        'delta_age'     => round($bluegrass_age - $age, 1),
        'whtr'          => round($whtr, 2),
    ];
}



    /**
     * List provider records
     */
    // public function listProviderRecords(Request $request)
    // {
    //     try {
    //         $user = auth('api')->user();
    //         if (!$user || !$user->spike_token) {
    //             return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
    //         }

    //         $fromTimestamp = $request->input('from_timestamp');
    //         if (!$fromTimestamp) {
    //             return response()->json(['success' => false, 'message' => 'from_timestamp is required'], 422);
    //         }

    //         $records = $this->spikeAuth->getProviderRecords(
    //             'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiIxMTQwMCIsInN1YiI6IjQwIn0.I7JbxPFoGrtXoh9IwQyByJ-PB9P7CjEG-po8RhEavrU',
    //             $fromTimestamp,
    //             $request->input('to_timestamp', now()->toIso8601String()),
    //             $this->getRepeatedQueryParam($request, 'providers'),
    //             $this->getRepeatedQueryParam($request, 'metrics'),
    //             $request->boolean('include_provider_specific_metrics', true)
    //         );

    //         if ($records === false) {
    //             return response()->json(['success' => false, 'message' => 'Failed to fetch provider records'], 500);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Provider records fetched successfully',
    //             'data' => $records
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Exception: ' . $e->getMessage()], 500);
    //     }
    // }

    /**
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

        $providers = $this->getRepeatedQueryParam($request, 'providers');
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
     * List workouts data
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

    /**
     * Get single workout record by ID
     */
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

    /**
     * Get interval statistics
     */
    public function getIntervalStatistics(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json(['success' => false, 'message' => 'User not authenticated with Spike'], 401);
        }

        $types = $this->getRepeatedQueryParam($request, 'types');
        if (empty($types)) {
            return response()->json(['success' => false, 'message' => 'types query parameter is required'], 422);
        }

        $params = $request->only([
            'from_timestamp',
            'to_timestamp',
            'interval',
            'include_record_ids'
        ]);
        $params['types'] = $types;

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

    /**
     * Get Daily Statistics
     */
    public function getDailyStatistics(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated with Spike'
            ], 401);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $types = $this->getRepeatedQueryParam($request, 'types');
        $providers = $this->getRepeatedQueryParam($request, 'providers');
        $excludeManual = $request->boolean('exclude_manual', false);
        $includeRecordIds = $request->boolean('include_record_ids', false);
        $deviceTypes = $this->getRepeatedQueryParam($request, 'device_types');

        // Validate required fields
        if (!$fromDate || !$toDate) {
            return response()->json([
                'success' => false,
                'message' => 'from_date and to_date are required'
            ], 422);
        }

        if (empty($types)) {
            return response()->json([
                'success' => false,
                'message' => 'types query parameter is required'
            ], 422);
        }

        try {
            // Call SpikeService to get daily statistics
            $data = $this->spikeAuth->getDailyStatistics(
                $user->spike_token,
                $fromDate,
                $toDate,
                $types,
                $providers,
                $excludeManual,
                $includeRecordIds,
                $deviceTypes
            );

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch daily statistics'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Time Series
     */
    public function getTimeSeries(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || !$user->spike_token) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated with Spike'
            ], 401);
        }

        $fromTimestamp = $request->input('from_timestamp');
        $toTimestamp = $request->input('to_timestamp');
        $metric = $request->input('metric');
        $providers = $this->getRepeatedQueryParam($request, 'providers');
        $includeRecordIds = $request->boolean('include_record_ids', false);
        $mergeMethod = $request->input('merge_method');
        $deviceTypes = $this->getRepeatedQueryParam($request, 'device_types');

        // Validate required fields
        if (!$fromTimestamp || !$toTimestamp || !$metric) {
            return response()->json([
                'success' => false,
                'message' => 'from_timestamp, to_timestamp, and metric are required'
            ], 422);
        }

        try {
            // Call SpikeService to get time series
            $data = $this->spikeAuth->getTimeSeries(
                $user->spike_token,
                $fromTimestamp,
                $toTimestamp,
                $metric,
                $providers,
                $includeRecordIds,
                $mergeMethod,
                $deviceTypes
            );

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch time series data'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request)

    {

        $userProvider = new UserProviders();
        $userProvider->user_id = $request->user_id;
        $userProvider->provider_slug = $request->provider_slug;
        $userProvider->save(); // saving to database

        return response()->json([
            'success' => true,
            'message' => 'User provider created successfully.',
            'data' => $userProvider
        ]);
    }
}
