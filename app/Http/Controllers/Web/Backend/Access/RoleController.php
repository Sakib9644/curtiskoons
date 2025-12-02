<?php

namespace App\Http\Controllers\Web\Backend\Access;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('id', 'desc')->paginate(25);
        return view('backend.layouts.access.roles.index', [
            'roles' => $roles
        ]);
    }

    public function create()
    {
        return view('backend.layouts.access.roles.create', [
            'permissions' => Permission::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'permissions' => 'required',
            'guard_name' => 'required|in:web,api'
        ]);
        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => $request->guard_name]);
            $role->syncPermissions($request->permissions);
            return redirect()->route('admin.roles.index')->with('t-success', 'Role created t-successfully');
        } catch (Exception $exception) {
            return redirect()->back()->with('t-error', $exception->getMessage());
        }
    }

    public function edit($id)
    {
        $role = Role::find($id);
        return view('backend.layouts.access.roles.edit', [
            'role' => $role,
            'permissions' => Permission::where('guard_name', $role->guard_name)->get()
        ]);
    }

public function update(Request $request, $id)
{
    // Validate input
    $request->validate([
        'name' => 'required|string|max:255',
        'permissions' => 'required|array', // array of permission names
        'guard_name' => 'required|in:web,api'
    ]);

    try {
        // Find the role
        $role = Role::findOrFail($id);

        // Update role name and guard
        $role->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

      
        $role->syncPermissions($request->permissions);

        foreach ($role->users as $user) {
            $user->permissions()->detach();
        }

        return redirect()->route('admin.roles.index')->with('t-success', 'Role updated successfully');
    } catch (\Exception $exception) {
        return redirect()->back()->with('t-error', $exception->getMessage());
    }
}

    public function show($id)
    {
        return view('backend.layouts.access.roles.edit', [
            'role' => Role::find($id),
            'permissions' => Permission::all()
        ]);
    }
    public function destroy($id)
    {
        try {
            $role = Role::find($id);
            $role->delete();
            return redirect()->back()->with('t-success', 'Role deleted t-successfully');
        } catch (Exception $exception) {
            return redirect()->back()->with('t-error', $exception->getMessage());
        }
    }
}
