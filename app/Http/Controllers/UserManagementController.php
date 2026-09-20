<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AuditLog;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('cms.users.index', [
            'users' => User::where('role', '!=', 'developer')->orderBy('name')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = new User($validated);
        $user->is_admin = true;
        $user->role = 'admin';
        $user->save();
        AuditLog::record('user.created', 'Administrator '.$user->email.' dibuat.', $user);

        return redirect()->route('cms.users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(int $user)
    {
        return view('cms.users.edit', ['managedUser' => $this->managedUser($user)]);
    }

    public function update(Request $request, int $user)
    {
        $managedUser = $this->managedUser($user);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($managedUser)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }
        $managedUser->fill($validated);
        $managedUser->is_admin = true;
        $managedUser->role = 'admin';
        $managedUser->is_active = $request->boolean('is_active');
        $managedUser->save();
        AuditLog::record('user.updated', 'Administrator '.$managedUser->email.' diperbarui.', $managedUser);

        return redirect()->route('cms.users.index')->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(int $user)
    {
        $managed = $this->managedUser($user); AuditLog::record('user.deleted', 'Administrator '.$managed->email.' dihapus.', $managed); $managed->delete();

        return redirect()->route('cms.users.index')->with('status', 'Pengguna berhasil dihapus.');
    }

    private function managedUser(int $id): User
    {
        return User::whereKey($id)->where('role', '!=', 'developer')->firstOrFail();
    }
}
