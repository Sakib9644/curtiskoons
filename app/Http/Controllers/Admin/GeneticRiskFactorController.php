<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneticRiskFactor;

class GeneticRiskFactorController extends Controller
{
    // List all factors with pagination
    public function index()
    {
        $factors = GeneticRiskFactor::orderBy('id', 'desc')->paginate(10);
        return view('backend.layouts.genetic_risk_factors.index', compact('factors'));
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
