<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with(['roles:id,name', 'clinic:id,name'])
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")))
            ->when($request->role, fn ($q, $v) => $q->role($v))
            ->when($request->clinic_id, fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($users);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'permissions', 'clinic']);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:50',
            'clinic_id' => 'nullable|exists:clinics,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'clinic_id' => $validated['clinic_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        return response()->json($user->load(['roles', 'clinic']), 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'sometimes|string|exists:roles,name',
            'phone' => 'nullable|string|max:50',
            'clinic_id' => 'nullable|exists:clinics,id',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => ($validated['first_name'] ?? $user->first_name).' '.($validated['last_name'] ?? $user->last_name),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'clinic_id' => $validated['clinic_id'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'password' => ! empty($validated['password']) ? Hash::make($validated['password']) : $user->password,
        ]);

        if (! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json($user->load(['roles', 'clinic']));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        if ($user->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot delete super admin'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot change your own role'], 403);
        }

        if ($user->hasRole('super_admin') && $validated['role'] !== 'super_admin') {
            return response()->json(['message' => 'Cannot demote super admin'], 403);
        }

        $user->syncRoles([$validated['role']]);

        return response()->json($user->load('roles'));
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot toggle your own status'], 403);
        }

        if ($user->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot deactivate super admin'], 403);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json($user);
    }

    public function roles()
    {
        return response()->json(Role::with('permissions')->get());
    }

    public function permissions()
    {
        return response()->json(Permission::all());
    }
}
