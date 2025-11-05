<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticContent;
use Illuminate\Http\Request;


class StaticContentController extends Controller
{
    /**
     * Create a new static content entry
     */
    public function create(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'content' => 'required|string',
        ]);

        $staticContent = new StaticContent();
        $staticContent->type = $request->type;
        $staticContent->content = $request->content;
        $staticContent->save();

        return response()->json([
            'message' => 'Static content created successfully',
            'data' => $staticContent
        ]);
    }

    /**
     * Update existing static content by type
     */
    public function update(Request $request, $type)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $staticContent = StaticContent::where('type', $type)->first();
        if (!$staticContent) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        $staticContent->content = $request->content;
        $staticContent->save();

        return response()->json([
            'message' => 'Static content updated successfully',
            'data' => $staticContent
        ]);
    }

    /**
     * Get static content by type
     */
    public function getContent($type)
    {
        $staticContent = StaticContent::where('type', $type)->first();

        if (!$staticContent) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        return response()->json($staticContent);
    }

    /**
     * Get all static contents
     */
    public function getAll()
    {
        $contents = StaticContent::all();

        return response()->json([
            'message' => 'Static contents retrieved successfully',
            'success' => true,
            'data' => $contents
        ]);
    }
}
