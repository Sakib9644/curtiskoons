<?php

namespace App\Http\Controllers;

use App\Services\SpikeService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpikeController extends Controller
{
    public $spike;

    public function __construct(SpikeService $spike)
    {
        $this->spike = $spike;
    }

    /**
     * Initiate provider connection (API route)
     */
    public function initiateProviderConnection(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:garmin,fitbit,strava,oura,apple_health,whoop',
        ]);

        $provider = $request->input('provider');
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $applicationUserId = $this->spike->getApplicationUserId($user);

        // Use user_id in query param so web callback can identify the user
        $callbackUrl = route('spike.callback', ['user_id' => $user->id]);

        $redirectUrl = "{$this->spike->baseUrl}/provider/{$provider}/auth?application_id={$this->spike->appId}&application_user_id={$applicationUserId}&redirect_uri=" . urlencode($callbackUrl);

        return response()->json(['redirect_url' => $redirectUrl]);
    }

    /**
     * Callback after OAuth consent (web route)
     */
    public function providerCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'provider' => 'required|string|in:garmin,fitbit,strava,oura,apple_health,whoop',
            'user_id' => 'required|integer'
        ]);

        $user = User::find($request->input('user_id'));
        if (!$user) {
            return redirect('/')->with('error', 'User not found.');
        }

        $code = $request->input('code');
        $provider = $request->input('provider');

        try {
            // Exchange code for provider token
            $response = Http::asJson()
                ->post("{$this->spike->baseUrl}/provider/{$provider}/token", [
                    'application_id' => $this->spike->appId,
                    'application_user_id' => $this->spike->getApplicationUserId($user),
                    'code' => $code,
                ]);

            if ($response->failed()) {
                throw new \Exception('Provider token exchange failed: ' . $response->body());
            }

            // Store token / re-authenticate user
            $this->spike->authenticateUser($user);

            return redirect('/dashboard')->with('success', "Connected to {$provider}!");
        } catch (\Exception $e) {
            Log::error('Spike callback error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
            return redirect('/dashboard')->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Fetch provider data (API)
     */
    public function fetchProviderData(Request $request)
    {
        $request->validate([
            'provider' => 'nullable|string|in:garmin,fitbit,strava,oura,apple_health,whoop,all',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $provider = $request->input('provider');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$this->spike->getAccessToken($user)) {
            return response()->json(['error' => 'User not connected to Spike. Connect first.'], 401);
        }

        try {
            $stats = $this->spike->getDailyStats($user, $startDate, $endDate, $provider);
            $workouts = $this->spike->getWorkouts($user, $provider);

            return response()->json([
                'provider' => $provider ?? 'all',
                'daily_stats' => $stats,
                'workouts' => $workouts,
            ]);
        } catch (\Exception $e) {
            Log::error('Spike data fetch error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Connect and fetch initial data (API)
     */
    public function connectAndFetch(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if (!$this->spike->getAccessToken($user)) {
            $this->spike->authenticateUser($user);
        }

        $stats = $this->spike->getDailyStats($user, '2025-10-01', '2025-10-18');

        return response()->json($stats);
    }
}
