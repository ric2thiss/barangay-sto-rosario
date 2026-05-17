<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::all();

        $users = User::with('role')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('username', 'like', "%$search%");
                });
            })
            ->when($request->role, fn($q, $role) => $q->where('role_id', $role))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        $stats = [
            'Total Users' => User::count(),
            'Admins'      => User::whereHas('role', fn($q) => $q->where('role_name', 'Admin'))->count(),
            'Secretaries' => User::whereHas('role', fn($q) => $q->where('role_name', 'Secretary'))->count(),
            'Residents'   => User::whereHas('role', fn($q) => $q->where('role_name', 'Resident'))->count(),
        ];

        return view('admin.users.index', compact('users', 'roles', 'stats'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'username'              => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_id'               => ['required', 'exists:roles,role_id'],
            'status'                => ['in:active,inactive'],
            'password'              => ['required', 'confirmed', Rules\Password::defaults()],
             'is_resident' => ['boolean'],   // add
            'is_of_age'   => ['boolean'],   // add
            ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status']   = $validated['status'] ?? 'active';

      // store()
$validated['is_resident'] = (bool) $request->input('is_resident', false);
$validated['is_of_age']   = (bool) $request->input('is_of_age', false);

User::create($validated);

        return redirect()->route('admin.users.index')
                         ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->user_id . ',user_id'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->user_id . ',user_id'],
            'role_id'  => ['required', 'exists:roles,role_id'],
            'status'   => ['in:active,inactive'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_resident' => ['boolean'],   // add
            'is_of_age'   => ['boolean'],   // add
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // update()
$validated['is_resident'] = (bool) $request->input('is_resident', false);
$validated['is_of_age']   = (bool) $request->input('is_of_age', false);

$user->update($validated);

        return redirect()->route('admin.users.index')
                         ->with('success', 'User updated successfully.');
    }
//old delete
    // public function destroy(User $user)
    // {
    //     if ($user->user_id === auth()->id()) {
    //         return redirect()->route('admin.users.index')
    //                          ->with('error', 'You cannot delete your own account.');
    //     }

    //     $user->delete();

    //     return redirect()->route('admin.users.index')
    //                      ->with('success', 'User deleted successfully.');
    // }

    public function destroy(User $user)
{
    if ($user->user_id === auth()->id()) {
        return redirect()->route('admin.users.index')
                         ->with('error', 'You cannot deactivate your own account.');
    }

    if (request('action') === 'activate') {
        $user->update(['status' => 'active']);
        return redirect()->route('admin.users.index')
                         ->with('success', 'User has been activated successfully.');
    }

    $user->update(['status' => 'deactivated']);

    return redirect()->route('admin.users.index')
                     ->with('success', 'User has been deactivated successfully.');
}
}