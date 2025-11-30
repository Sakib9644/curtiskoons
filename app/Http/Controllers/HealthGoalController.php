<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HealthGoal;
use Yajra\DataTables\DataTables;

class HealthGoalController extends Controller
{
    // List all goals with pagination

public function index(Request $request)
{
    if ($request->ajax()) {
        $healthGoals = HealthGoal::with('user')->select('health_goals.*');

        return DataTables::of($healthGoals)
            ->addIndexColumn()
            ->addColumn('user', function($row){
                return $row->user ? $row->user->name . ' (' . $row->user->email . ')' : 'N/A';
            })
            ->addColumn('action', function($row){
                $btn = '<a href="'.route('admin.health_goals.edit', $row->id).'" class="btn btn-sm btn-warning me-1">Edit</a>';
                $btn .= '<form action="'.route('admin.health_goals.destroy', $row->id).'" method="POST" class="d-inline-block" onsubmit="return confirm(\'Are you sure?\');">'
                        .csrf_field().method_field('DELETE').
                        '<button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>';
                return $btn;
            })
            ->rawColumns(['action', 'methods'])
            ->make(true);
    }

    return view('backend.layouts.goals.index');
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
