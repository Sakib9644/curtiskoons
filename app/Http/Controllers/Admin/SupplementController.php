<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplement;
use App\Models\User;

class SupplementController extends Controller
{
    // List all supplements with pagination
    public function index()
    {
        $supplements = Supplement::with('user')->orderBy('id', 'desc')->paginate(10);
        return view('backend.layouts.supplements.index', compact('supplements'));
    }

    // Show create form
    public function create()
    {
        $users = User::select('id', 'name', 'email')->withoutRole('admin')->get();
        return view('backend.layouts.supplements.create', compact('users'));
    }

    // Store new supplement
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'nullable|string',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        Supplement::create([
            'name' => $request->name,
            'dosage' => $request->dosage,
            'description' => $request->description,
            'user_id' => $request->user_id,
        ]);

        return redirect()->route('admin.supplements.index')->with('t-success', 'Supplement added successfully.');
    }

    // Show edit form
    public function edit(Supplement $supplement)
    {
        $users = User::select('id', 'name', 'email')->get();
        return view('backend.layouts.supplements.edit', compact('supplement', 'users'));
    }

    // Update supplement
    public function update(Request $request, Supplement $supplement)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'nullable|string',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $supplement->update([
            'name' => $request->name,
            'dosage' => $request->dosage,
            'description' => $request->description,
            'user_id' => $request->user_id,
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
