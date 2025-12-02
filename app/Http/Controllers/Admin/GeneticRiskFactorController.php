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
        $factors = GeneticRiskFactor::with('user')->latest();

        return DataTables::of($factors)
            ->addIndexColumn()
            ->addColumn('user', function($factor) {
                return $factor->user->email ?? '-';
            })
             ->addColumn('description', function($row){
                return $row->description; // Summernote HTML will be rendered
            })
            ->addColumn('action', function($factor) {
                $buttons = '';

                // Show "view" button
               
                // Show "update" button
                if(auth()->user()->can('update')) {
                    $buttons .= '<a href="'.route('admin.genetic_risk_factors.edit', $factor->id).'" class="btn btn-primary btn-sm">Edit</a> ';
                }

                // Show "delete" button
                if(auth()->user()->can('delete')) {
                    $buttons .= '<form action="'.route('admin.genetic_risk_factors.destroy', $factor->id).'" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure?\')">'
                        .csrf_field().method_field('DELETE').
                        '<button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>';
                }

                return $buttons;
            })
            ->rawColumns(['action','description']) // render HTML
            ->make(true);
    }

    // Check insert permission for "Add New" button
    $canInsert = auth()->user()->can('insert');

    return view('backend.layouts.genetic_risk_factors.index', compact('canInsert'));
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
