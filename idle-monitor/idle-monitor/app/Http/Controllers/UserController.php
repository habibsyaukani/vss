<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /**
     * Show users list page
     */
    public function index()
    {
        return view('admin.user.index');
    }

    /**
     * Get users data for DataTable (AJAX)
     */
    public function data()
    {
        $users = User::query();

        return DataTables::of($users)
            ->addColumn('role_badge', function ($user) {
                if ($user->role === 'admin') {
                    $class = 'bg-danger';
                    $icon = '<i class="fas fa-shield-alt me-1"></i>';
                } else {
                    $class = 'bg-primary';
                    $icon = '<i class="fas fa-user me-1"></i>';
                }
                return '<span class="badge ' . $class . ' text-white px-2 py-1">' . $icon . ucfirst(str_replace('_', ' ', $user->role)) . '</span>';
            })
            ->addColumn('status_badge', function ($user) {
                if ($user->status === 'active') {
                    $class = 'bg-success';
                    $icon = '<i class="fas fa-check-circle me-1"></i>';
                } else {
                    $class = 'bg-secondary';
                    $icon = '<i class="fas fa-times-circle me-1"></i>';
                }
                return '<span class="badge ' . $class . ' text-white px-2 py-1">' . $icon . ucfirst($user->status) . '</span>';
            })
            ->addColumn('created_at_formatted', function ($user) {
                return $user->created_at->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($user) {
                return '
                    <a href="' . route('admin.user.edit', $user->id) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $user->id . '">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ';
            })
            ->rawColumns(['role_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show create user form
     */
    public function create()
    {
        return view('admin.user.form');
    }

    /**
     * Store new user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:admin,fleet_manager',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.user.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Show edit user form
     */
    public function edit(User $user)
    {
        return view('admin.user.form', compact('user'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'role' => 'required|in:admin,fleet_manager',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.user.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully!']);
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $newPassword = 'Password123!';

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return response()->json([
            'message' => 'Password reset successfully!',
            'temporary_password' => $newPassword,
        ]);
    }
}
