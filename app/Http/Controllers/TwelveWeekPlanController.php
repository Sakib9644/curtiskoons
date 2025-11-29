<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TwelveWeekPlan;

class TwelveWeekPlanController extends Controller
{
    // List all plans with pagination
    public function index()
    {
        $plans = TwelveWeekPlan::orderBy('id', 'desc')->paginate(10);
        return view('backend.layouts.twelve_week_plans.index', compact('plans'));
    }

    // Show create form
    public function create()
    {
        return view('backend.layouts.twelve_week_plans.create');
    }

    // Store new plan
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        TwelveWeekPlan::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.twelve_week_plans.index')
                         ->with('t-success', '12-Week Plan created successfully.');
    }

    // Show edit form
    public function edit(TwelveWeekPlan $twelveWeekPlan)
    {
        return view('backend.layouts.twelve_week_plans.edit', compact('twelveWeekPlan'));
    }

    // Update plan
    public function update(Request $request, TwelveWeekPlan $twelveWeekPlan)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $twelveWeekPlan->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

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
