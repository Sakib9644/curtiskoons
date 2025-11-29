<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplement;

class SupplementController extends Controller
{
    // List all supplements with pagination
    public function index()
    {
        $supplements = Supplement::orderBy('id', 'desc')->paginate(10);
        return view('backend.layouts.supplements.index', compact('supplements'));
    }

    // Show create form
    public function create()
    {
        return view('backend.layouts.supplements.create');
    }

    // Store new supplement
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'dosage' => 'nullable|string',
        'description' => 'nullable|string',
    ]);

    Supplement::create([
        'name' => $request->name,
        'dosage' => $request->dosage,
        'description' => $request->description,
    ]);

    return redirect()->route('admin.supplements.index')->with('t-success', 'Supplement added successfully.');
}

    // Show edit form
    public function edit(Supplement $supplement)
    {
        return view('backend.layouts.supplements.edit', compact('supplement'));
    }

    // Update supplement
 public function update(Request $request, Supplement $supplement)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'dosage' => 'nullable|string',
        'description' => 'nullable|string',
    ]);

    $supplement->update([
        'name' => $request->name,
        'dosage' => $request->dosage,
        'description' => $request->description,
    ]);

    return redirect()->route('admin.supplements.index')->with('t-success', 'Supplement updated successfully.');
}


    // Delete supplement
    public function destroy(Supplement $supplement)
    {
        $supplement->delete();

        return redirect()->route('admin.supplements.index')->with('t-success', 'Supplement deleted successfully.');
    }
}
