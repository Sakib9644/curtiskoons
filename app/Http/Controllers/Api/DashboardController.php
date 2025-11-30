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
}
