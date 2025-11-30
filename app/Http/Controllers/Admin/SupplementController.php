<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplement;
use App\Models\User;
use Yajra\DataTables\DataTables;

class SupplementController extends Controller
{
    // List all supplements with pagination
 public function index(Request $request)
{
    if ($request->ajax()) {
        $supplements = \App\Models\Supplement::with('user')->select('supplements.*');

        return Datatables::of($supplements)
            ->addIndexColumn()
            ->addColumn('user', function($row) {
                return $row->user ? $row->user->name . ' (' . $row->user->email . ')' : 'N/A';
            })
            ->addColumn('dosage', function($row) {
                return $row->dosage; // render HTML from Summernote
            })
            ->addColumn('action', function($row) {
                $edit = '<a href="'.route('admin.supplements.edit', $row->id).'" class="btn btn-sm btn-warning me-1">Edit</a>';
                $delete = '<form action="'.route('admin.supplements.destroy', $row->id).'" method="POST" class="d-inline-block" onsubmit="return confirm(\'Are you sure?\');">'
                        .csrf_field().method_field('DELETE').
                        '<button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>';
                return $edit . $delete;
            })
            ->rawColumns(['dosage', 'action'])
            ->make(true);
    }

    return view('backend.layouts.supplements.index');
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
