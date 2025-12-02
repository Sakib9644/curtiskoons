<?php

namespace App\Http\Controllers\Web\Backend\Access;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with(['roles', 'assignedgroup']);

            return DataTables::of($users)
                ->addIndexColumn()

                // User column: HTML for display
                ->addColumn('user', function ($user) {
                    $initial = strtoupper(substr($user->name, 0, 1));
                    return '<div class="d-flex align-items-center">
                            <div class="user-avatar me-3">' . $initial . '</div>
                            <div>
                                <h6 class="mb-0 fw-semibold">' . $user->name . '</h6>
                                <small class="text-muted">' . $user->email . '</small>
                            </div>
                        </div>';
                })

                // Hidden column for searching
           


                // Role column
                ->addColumn('role', function ($user) {
                    return $user->roles->pluck('name')->join(', ') ?: 'No Role';
                })

                // Group column
                ->addColumn('group', function ($user) {
                    return $user->assignedgroup
                        ? '<span class="badge bg-primary bg-opacity-10">' . $user->assignedgroup->name . '</span>'
                        : '<span class="text-muted">Not Assigned</span>';
                })

                // Created_at column
                ->addColumn('created_at', function ($user) {
                    return '<div class="d-flex flex-column">
                            <span class="fw-medium">' . $user?->created_at?->format('Y-M-d') . '</span>
                        </div>';
                })

                // Action buttons
                ->addColumn('action', function ($user) {
                    $buttons = '<div class="action-btn-group">';
                    if (auth()->user()->can('view')) {
                        $buttons .= '<a href="' . route('admin.users.show', $user->id) . '" class="btn btn-sm btn-info me-1">View</a>';
                    }
                    if (auth()->user()->can('update')) {
                        $buttons .= '<a href="' . route('admin.users.edit', $user->id) . '" class="btn btn-sm btn-primary me-1">Edit</a>';
                        $buttons .= '<button class="btn btn-sm btn-secondary me-1" data-bs-toggle="modal" data-bs-target="#assignGroupModal"
                                onclick="assignGroup(' . $user->id . ', \'' . $user->name . '\')">Assign Group</button>';
                    }
                    if (auth()->user()->can('delete')) {
                        $buttons .= '<form action="' . route('admin.users.destroy', $user->id) . '" method="POST" class="d-inline ms-1" onsubmit="return confirm(\'Are you sure you want to delete this user?\')">'
                            . csrf_field() . method_field('DELETE') .
                            '<button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>';
                    }
                    $buttons .= '</div>';
                    return $buttons;
                })

                ->rawColumns(['user', 'group', 'action', 'created_at','email'])
                ->make(true);
        }

        return view('backend.layouts.access.users.index');
    }


    public function create()
    {
        return view('backend.layouts.access.users.create', ['roles' => Role::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->slug = Str::slug($user->name . time());
        $user->save();

        foreach ($request->roles as $role) {
            DB::table('model_has_roles')->insert([
                'role_id' => $role,
                'model_type' => 'App\Models\User',
                'model_id' => $user->id
            ]);
        }

        return redirect()->route('admin.users.index')->with('t-success', 'User created t-successfully');
    }

    public function show($id)
    {
        $user = User::with(['profile'])->find($id);
        return view('backend.layouts.access.users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::all();
        return view('backend.layouts.access.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $id,
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            DB::table('model_has_roles')->where('model_id', $id)->delete();

            foreach ($request->roles as $role) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $role,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id
                ]);
            }

            return redirect()->back()->with('t-success', 'User updated t-successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        DB::table('model_has_roles')->where('model_id', $id)->delete();
        $user->delete();
        return redirect()->back()->with('t-success', 'User deleted t-successfully');
    }

    public function addtogroup(Request $request)
    {


        try {
            $request->validate([

                'user_id' => 'required|exists:users,id',
                'group_id' => 'required|exists:groups,id'

            ]);
            $user = User::find($request->user_id);
            $user->group_id = $request->group_id;
            $user->save();
            return redirect()->route('admin.users.index')->with('t-success', 'User Added to This Group Successfully');
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->route('admin.users.index')->with('t-error', $th->getMessage());
        }
    }

    public function status(int $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            redirect()->back()->with('t-error', 'User not found');
        }
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();
        session()->put('t-success', 'Status updated successfully');
        return view('backend.layouts.access.users.show', compact('user'));
    }

    public function card($slug)
    {

        $user = User::where('slug', $slug)->first();
        $logoBase64 = base64_encode(file_get_contents(public_path('default/logo.png')));
        $whitelogoBase64 = base64_encode(file_get_contents(public_path('default/logo.png')));
        $backLogoBase64 = base64_encode(file_get_contents(public_path('default/logo.png')));

        $avatarPath = public_path(
            $user->avatar && file_exists(public_path($user->avatar)) ? $user->avatar : 'default/profile.jpg'
        );

        $avatarBase64 = base64_encode(file_get_contents($avatarPath));

        //for pdf
        /* $qrCode = base64_encode(QrCode::size(90)->generate(route('admin.users.card', $user->slug)));
        $pdf = Pdf::loadView('card.pdf', compact('user', 'logoBase64', 'whitelogoBase64', 'avatarBase64', 'qrCode', 'backLogoBase64'))->setPaper('a4', 'portrait');
        return $pdf->stream();  */

        //for web
        $qrCode = QrCode::size(90)->generate(route('admin.users.card', $user->slug));
        return view('card.web', compact('user', 'logoBase64', 'whitelogoBase64', 'avatarBase64', 'qrCode', 'backLogoBase64'));
    }
}
