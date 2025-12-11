<?php

namespace App\Http\Controllers;

use App\Models\Biomarker;
use App\Models\BiomarkerGenetic;
use App\Models\BiomarkerRange;
use Illuminate\Http\Request;

class BiomarkerController extends Controller
{
    /**
     * Display a listing of biomarkers
     */
    public function index()
    {
        $biomarkers = Biomarker::with(['ranges', 'genetics'])
            ->orderBy('category')
            ->paginate(20);

        return view('backend.biomarker.index', compact('biomarkers'));
    }

    /**
     * Show the form for editing the specified biomarker
     */
    public function edit(Biomarker $biomarker)
    {
        $biomarker->load(['ranges', 'genetics']);

        return view('backend.biomarker.edit', compact('biomarker'));
    }

    /**
     * Update the specified biomarker
     */
    public function update(Request $request, Biomarker $biomarker)
    {
        // Validate basic biomarker info
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'category' => 'required|string',
        ]);

        // Update biomarker
        $biomarker->update($validated);

        // If numeric biomarker, update ranges
        if ($biomarker->is_numeric && $request->has('ranges')) {
            // Delete old ranges
            $biomarker->ranges()->delete();

            // Create new ranges
            foreach ($request->ranges as $rangeData) {
                BiomarkerRange::create([
                    'biomarker_id' => $biomarker->id,
                    'range_start' => $rangeData['range_start'],
                    'range_end' => $rangeData['range_end'],
                    'delta' => $rangeData['delta'],
                ]);
            }
        }

        // If genetic biomarker, update genetics
        if (!$biomarker->is_numeric && $request->has('genetics')) {
            // Delete old genetics
            $biomarker->genetics()->delete();

            // Create new genetics
            foreach ($request->genetics as $geneticData) {
                BiomarkerGenetic::create([
                    'biomarker_id' => $biomarker->id,
                    'genotype' => $geneticData['genotype'],
                    'delta' => $geneticData['delta'],
                ]);
            }
        }

        return redirect()->route('admin.biomarker.index')
            ->with('success', 'Biomarker updated successfully!');
    }

    /**
     * Remove the specified biomarker
     */
    public function destroy(Biomarker $biomarker)
    {
        $biomarker->delete();

        return redirect()->route('admin.biomarker.index')
            ->with('success', 'Biomarker deleted successfully!');
    }
}
