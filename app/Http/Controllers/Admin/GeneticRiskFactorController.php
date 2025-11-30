<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneticRiskFactor;
use Yajra\DataTables\DataTables;

class GeneticRiskFactorController extends Controller
{
    // List all factors with pagination

public function index(Request $request)
{
    if ($request->ajax()) {
        $factors = GeneticRiskFactor::with('user')->select('genetic_risk_factors.*');

        return DataTables::of($factors)
            ->addIndexColumn()
            ->addColumn('user', function($row){
                return $row->user ? $row->user->email . ' (' . $row->user->name . ')' : 'N/A';
            })
            ->addColumn('action', function($row){
                $btn = '<a href="'.route('admin.genetic_risk_factors.edit', $row->id).'" class="btn btn-sm btn-warning">Edit</a> ';
                $btn .= '<form action="'.route('admin.genetic_risk_factors.destroy', $row->id).'" method="POST" style="display:inline-block;">
                            '.csrf_field().method_field('DELETE').'
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</button>
                         </form>';
                return $btn;
            })
            ->rawColumns(['action', 'description'])
            ->make(true);
    }

    return view('backend.layouts.genetic_risk_factors.index');
}

    // Show create form
    public function create()
    {
        return view('backend.layouts.genetic_risk_factors.create');
    }

    // Store new factor
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $geneticRiskFactor = new GeneticRiskFactor();
        $geneticRiskFactor->title = $validated['title'];
        $geneticRiskFactor->description = $validated['description'] ?? null;
        $geneticRiskFactor->user_id = $validated['user_id'];
        $geneticRiskFactor->save();

        return redirect()->route('admin.genetic_risk_factors.index')
                         ->with('t-success', 'Genetic Risk Factor created successfully.');
    }

    // Show edit form
    public function edit(GeneticRiskFactor $geneticRiskFactor)
    {
        return view('backend.layouts.genetic_risk_factors.edit', compact('geneticRiskFactor'));
    }

    // Update factor
    public function update(Request $request, GeneticRiskFactor $geneticRiskFactor)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $geneticRiskFactor->title = $validated['title'];
        $geneticRiskFactor->description = $validated['description'] ?? null;
        $geneticRiskFactor->user_id = $validated['user_id'];
        $geneticRiskFactor->save();

        return redirect()->route('admin.genetic_risk_factors.index')
                         ->with('t-success', 'Genetic Risk Factor updated successfully.');
    }

    // Delete factor
    public function destroy(GeneticRiskFactor $geneticRiskFactor)
    {
        $geneticRiskFactor->delete();

        return redirect()->route('admin.genetic_risk_factors.index')
                         ->with('t-success', 'Genetic Risk Factor deleted successfully.');
    }
}
