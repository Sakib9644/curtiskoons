<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TwelveWeekPlan;
use App\Models\User;

class TwelveWeekPlanController extends Controller
{
    // List all plans with pagination
    public function index()
    {
        $plans = TwelveWeekPlan::with('user')->orderBy('id', 'desc')->paginate(10);
        return view('backend.layouts.twelve_week_plans.index', compact('plans'));
    }

    // Show create form
    public function create()
    {
        // Pass users for dropdown
        $users = User::select('id', 'name', 'email')->withoutRole('admin')->get();
        return view('backend.layouts.twelve_week_plans.create', compact('users'));
    }

    // Store new plan
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $plan = new TwelveWeekPlan();
        $plan->title = $request->title;
        $plan->description = $request->description;
        $plan->user_id = $request->user_id; // assign to user
        $plan->save();

        return redirect()->route('admin.twelve_week_plans.index')
                         ->with('t-success', '12-Week Plan created successfully.');
    }

    // Show edit form
    public function edit(TwelveWeekPlan $twelveWeekPlan)
    {
        $users = \App\Models\User::select('id', 'name', 'email')->get();
        return view('backend.layouts.twelve_week_plans.edit', compact('twelveWeekPlan', 'users'));
    }

    // Update plan
    public function update(Request $request, TwelveWeekPlan $twelveWeekPlan)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $twelveWeekPlan->title = $request->title;
        $twelveWeekPlan->description = $request->description;
        $twelveWeekPlan->user_id = $request->user_id; // update user assignment
        $twelveWeekPlan->save();

        return redirect()->route('admin.twelve_week_plans.index')
                         ->with('t-success', '12-Week Plan updated successfully.');
    }

    // Delete plan
    public function destroy(TwelveWeekPlan $twelveWeekPlan)
    {
        $twelveWeekPlan->delete();

        return redirect()->route('admin.twelve_week_plans.index')
                         ->with('t-success', '12-Week Plan deleted successfully.');
    }
}
