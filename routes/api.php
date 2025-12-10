<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FirebaseTokenController;
use App\Http\Controllers\Api\Frontend\categoryController;
use App\Http\Controllers\Api\Frontend\FaqController;
use App\Http\Controllers\Api\Frontend\HomeController;
use App\Http\Controllers\Api\Frontend\ImageController;
use App\Http\Controllers\Api\Frontend\PageController;
use App\Http\Controllers\Api\Frontend\PostController;
use App\Http\Controllers\Api\Frontend\SubcategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Frontend\SettingsController;
use App\Http\Controllers\Api\Frontend\SocialLinksController;
use App\Http\Controllers\Api\Frontend\SubscriberController;
use App\Http\Controllers\Api\StaticContentController;
use App\Http\Controllers\LabReportController;
use App\Http\Controllers\SpikeController;
use App\Models\SpikeMetric;
use App\Models\User;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

//page
Route::get('/page/home', [HomeController::class, 'index']);
Route::get('/category', [categoryController::class, 'index']);
Route::get('/subcategory', [SubcategoryController::class, 'index']);
Route::get('/social/links', [SocialLinksController::class, 'index']);
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/faq', [FaqController::class, 'index']);
Route::post('subscriber/store', [SubscriberController::class, 'store'])->name('api.subscriber.store');

/*
# Post
*/
Route::middleware(['auth:api'])->controller(PostController::class)->prefix('auth/post')->group(function () {
    Route::get('/', 'index');
    Route::post('/store', 'store');
    Route::get('/show/{id}', 'show');
    Route::post('/update/{id}', 'update');
    Route::delete('/delete/{id}', 'destroy');
});
Route::get('/posts', [PostController::class, 'posts']);
Route::get('/post/show/{post_id}', [PostController::class, 'post']);

Route::middleware(['auth:api'])->controller(ImageController::class)->prefix('auth/post/image')->group(function () {
    Route::get('/', 'index');
    Route::post('/store', 'store');
    Route::get('/delete/{id}', 'destroy');
});
Route::get('dynamic/page', [PageController::class, 'index']);
Route::get('dynamic/page/show/{slug}', [PageController::class, 'show']);
/*
# Auth Route
*/
Route::group(['middleware' => 'guest:api'], function ($router) {
    //register
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']);
    Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);
    Route::post('/verify-otp', [RegisterController::class, 'VerifyEmail']);

    Route::POST('login', [LoginController::class, 'login'])->name('api.login');
    //forgot password
    Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
    Route::post('/otp-token', [ResetPasswordController::class, 'MakeOtpToken']);
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
    //social login
    Route::post('/social-login', [SocialLoginController::class, 'SocialLogin']);
});
Route::group(['middleware' => ['auth:api', 'api-otp']], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/account/switch', [UserController::class, 'accountSwitch']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/update-avatar', [UserController::class, 'updateAvatar']);
    Route::delete('/delete-profile', [UserController::class, 'destroy']);
});
/*
# Firebase Notification Route
*/
Route::middleware(['auth:api'])->controller(FirebaseTokenController::class)->prefix('firebase')->group(function () {
    Route::get("test", "test");
    Route::post("token/add", "store");
    Route::post("token/get", "getToken");
    Route::post("token/delete", "deleteToken");
});
/*
# In App Notification Route
*/
Route::middleware(['auth:api'])->controller(NotificationController::class)->prefix('notify')->group(function () {
    Route::get('test', 'test');
    Route::get('/', 'index');
    Route::get('status/read/all', 'readAll');
    Route::get('status/read/{id}', 'readSingle');
});
/*
# Chat Route
*/
Route::middleware(['auth:api'])->controller(ChatController::class)->prefix('auth/chat')->group(function () {
    Route::get('/list', 'list');
    Route::post('/send/{receiver_id}', 'send');
    Route::get('/conversation/{receiver_id}', 'conversation');
    Route::get('/room/{receiver_id}', 'room');
    Route::get('/search', 'search');
    Route::get('/seen/all/{receiver_id}', 'seenAll');
    Route::get('/seen/single/{chat_id}', 'seenSingle');
});

/*
# CMS
*/
Route::prefix('cms')->name('cms.')->group(function () {
    Route::get('home', [HomeController::class, 'index'])->name('home');
});



Route::post('data', function(Request $request) {
    $events = $request->json()->all();
    Log::info('Spike webhook received:', ['data' => $events]);

    if (!is_array($events)) {
        Log::warning('Spike webhook data is not an array: ' . json_encode($events));
        return response('Bad Request', 400);
    }

    foreach ($events as $event) {
        $userId = $event['application_user_id'] ?? null;
        $user = User::find($userId);

        if (!$user) {
            Log::warning('User not found for application_user_id: ' . $userId);
            continue;
        }

        $provider = $event['provider_slug'] ?? 'unknown';
        $eventType = $event['event_type'] ?? '';

        Log::info("Processing Spike event for user {$user->id}, provider {$provider}, type {$eventType}");

        // --- Only process record_change events ---
        if ($eventType === 'record_change') {
            $startDate = substr($event['earliest_record_start_at'] ?? '', 0, 10);
            $endDate = substr($event['latest_record_end_at'] ?? '', 0, 10);

            if (!$startDate || !$endDate) {
                Log::warning("Invalid dates for user {$user->id}, provider {$provider}");
                continue;
            }

            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $metrics = $event['metrics'] ?? [];

            while ($start <= $end) {
                SpikeMetric::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'provider_slug' => $provider,
                        'date' => $start->format('Y-m-d')
                    ],
                    [
                        'steps' => $metrics['steps'] ?? 0,
                        'hrv' => $metrics['hrv_rmssd'] ?? null,
                        'rhr' => $metrics['heartrate_resting'] ?? null,
                        'sleep_hours' => isset($metrics['sleep_duration'])
                            ? round($metrics['sleep_duration'] / (1000 * 60 * 60), 1)
                            : null,
                    ]
                );

                $start->modify('+1 day');
            }

            Log::info("Processed record_change for user {$user->id}, provider {$provider}");

        } else {
            // Just log other event types since we don't have the provider connections table
            Log::info("Received event type {$eventType} for user {$user->id}, skipping DB update");
        }
    }

    return response()->json(['success' => true]);
});
Route::prefix('spike')->name('spike.')->group(function () {
    // 🔐 Authentication
    Route::get('/authenticate', [SpikeController::class, 'authenticateUser'])->name('authenticate');

    Route::get('/connection', [SpikeController::class, 'connection'])->name('authenticates');

    // 🔗 Provider Integration
    Route::get('/integrate/{provider}', [SpikeController::class, 'integrateProvider'])->name('integrate');

    // 👤 User Info
    Route::get('/user', [SpikeController::class, 'getUserInfo'])->name('user');
    Route::get('/userproperties', [SpikeController::class, 'getUserProperties'])->name('userproperties');

    // 🧩 Provider Records
    Route::get('/provider-records', [SpikeController::class, 'listProviderRecords'])->name('provider-records');
    Route::get('/provider-records/{recordId}', [SpikeController::class, 'getProviderRecord'])->name('provider-record');

    // 😴 Sleep Data
    Route::get('/sleep', [SpikeController::class, 'listSleep'])->name('sleep');
    Route::get('/sleep/{sleepId}', [SpikeController::class, 'getSleepRecord'])->name('sleep.record');

    // 🏋️‍♂️ Workouts
    Route::get('/workouts', [SpikeController::class, 'listWorkouts'])->name('workouts');
    Route::get('/workouts/{id}', [SpikeController::class, 'getWorkoutById'])->name('workouts.single');

    // 📊 Interval Statistics
    Route::get('/statistics/interval', [SpikeController::class, 'getIntervalStatistics'])->name('statistics.interval');

    // 📅 Daily Statistics
    Route::get('/statistics/daily', [SpikeController::class, 'getDailyStatistics'])->name('statistics.daily');

    // 📈 Time Series
    Route::get('/timeseries', [SpikeController::class, 'getTimeSeries'])->name('timeseries');

    // Route::get('/store/user', [SpikeController::class, 'store'])->name('timeseries')
    ;
    Route::POST('/providerCallback', [SpikeController::class, 'providerCallback'])->name('store');

    Route::get('/connected/providers', [SpikeController::class, 'connectedusers'])->name('conneted');
});

Route::prefix('static-content')->group(function () {

    // Routes that require authentication
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [StaticContentController::class, 'create']);       // Create new content
        Route::POST('/{type}', [StaticContentController::class, 'update']);  // Update content by type
    });

    // Public routes
    Route::POST('/privacy/accept', [StaticContentController::class, 'privacy']);
     // Get content by type
    Route::get('/', [StaticContentController::class, 'getAll']);           // Get all content
});
Route::get('bluegrass-age-report', [LabReportController::class, 'calculateAndStore']);           // Get all content



    Route::get('/healthgoals', [DashboardController::class, 'healthgoals']);           // Get all content
    Route::get('/riskfactors', [DashboardController::class, 'riskfactors']);           // Get all content
    Route::get('/suppliments', [DashboardController::class, 'suppliments']);           // Get all content
    Route::get('/twelve_week', [DashboardController::class, 'twelve_week']);           // Get all content
