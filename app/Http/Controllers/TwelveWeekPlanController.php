<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TwelveWeekPlan;
use App\Models\User;
use Yajra\DataTables\DataTables;

class TwelveWeekPlanController extends Controller
{
    // List all plans with pagination
public function index(Request $request)
{
    if ($request->ajax()) {
        $plans = TwelveWeekPlan::with('user')->select('twelve_week_plans.*');

        return DataTables::of($plans)
            ->addIndexColumn()
            ->addColumn('user', function($row){
                return $row->user ? $row->user->name . ' (' . $row->user->email . ')' : 'N/A';
            })
            ->addColumn('description', function($row){
                return $row->description; // Summernote HTML will be rendered
            })
            ->addColumn('action', function($row){
                $buttons = '';

                // View button


                // Edit button
                if(auth()->user()->can('update')) {
                    $buttons .= '<a href="'.route('admin.twelve_week_plans.edit', $row->id).'" class="btn btn-sm btn-warning me-1">Edit</a>';
                }

                // Delete button
                if(auth()->user()->can('delete')) {
                    $buttons .= '<form action="'.route('admin.twelve_week_plans.destroy', $row->id).'" method="POST" class="d-inline-block" onsubmit="return confirm(\'Are you sure?\');">'
                        .csrf_field().method_field('DELETE').
                        '<button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>';
                }

                return $buttons;
            })
            ->rawColumns(['action', 'description']) // renders HTML from Summernote
            ->make(true);
    }

    // Check insert permission for "Add New" button
    $canInsert = auth()->user()->can('insert');

    return view('backend.layouts.twelve_week_plans.index', compact('canInsert'));
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
