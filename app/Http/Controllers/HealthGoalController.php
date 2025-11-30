<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HealthGoal;

class HealthGoalController extends Controller
{
    // List all goals with pagination
    public function index()
    {
        // paginate 10 items per page
        $healthGoals = HealthGoal::orderBy('id', 'desc')->paginate(10);

        return view('backend.layouts.goals.index', compact('healthGoals'));
    }

    // Show create form
    public function create()
    {
        return view('backend.layouts.goals.create');
    }

    // Store new goal
    public function store(Request $request)
    {
        $request->validate([
            'goal' => 'required|string|max:255',
            'methods' => 'nullable|string',
            'timeline_years' => 'nullable|numeric',
            'user_id' => 'required|exists:users,id',
        ]);

        $healthGoal = new HealthGoal();
        $healthGoal->goal = $request->goal;
        $healthGoal->methods = $request->methods;
        $healthGoal->timeline_years = $request->timeline_years;
        $healthGoal->user_id = $request->user_id;
        $healthGoal->save();

        return redirect()->route('admin.health_goals.index')->with('t-success', 'Health Goal created successfully.');
    }


    // Show edit form
    public function edit(HealthGoal $healthGoal)
    {
        return view('backend.layouts.goals.edit', compact('healthGoal'));
    }

    // Update goal
public function update(Request $request, HealthGoal $healthGoal)
{
    $request->validate([
        'goal' => 'required|string|max:255',
        'methods' => 'nullable|string',
        'timeline_years' => 'nullable|numeric',
        'user_id' => 'required|exists:users,id',
    ]);

    $healthGoal->goal = $request->goal;
    $healthGoal->methods = $request->methods;
    $healthGoal->timeline_years = $request->timeline_years;
    $healthGoal->user_id = $request->user_id;
    $healthGoal->save();

    return redirect()->route('admin.health_goals.index')->with('t-success', 'Health Goal updated successfully.');
}
    // Delete goal
    public function destroy(HealthGoal $healthGoal)
    {
        $healthGoal->delete();

        return redirect()->route('admin.health_goals.index')->with('t-success', 'Health Goal deleted successfully.');
    }
}
