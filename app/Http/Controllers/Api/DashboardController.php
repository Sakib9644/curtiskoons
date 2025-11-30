<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function healthgoals()
    {
        $user = auth('api')->user();

        // Load the relationship
        $user->load('healthgoals');

        return response()->json([
            'success' => true,
            'message' => "Health Goals Retrieved Successfully",
            'data' => $user->healthgoals
        ]);
    }
    public function riskfactors()
    {
        $user = auth('api')->user();

        // Load the relationship
        $user->load('riskfactors');

        return response()->json([
            'success' => true,
            'message' => "Risk-factors Retrieved Successfully",
            'data' => $user->riskfactors
        ]);
    }
    public function suppliments()
    {
        $user = auth('api')->user();

        // Load the relationship
        $user->load('suppliments');

        return response()->json([
            'success' => true,
            'message' => "Suppliments Retrieved Successfully",
            'data' => $user->suppliments
        ]);
    }
public function twelve_week()
{
    $user = auth('api')->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    // Load the relationship
    $user->load('twelve_week'); // make sure this matches your relationship name

    return response()->json([
        'success' => true,
        'message' => "Twelve Week Retrieved Successfully",
        'data' => $user->twelve_week
    ]);
}

}
