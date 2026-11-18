<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Tampilkan daftar akun pengguna (User Management)
     */
    public function index(Request $request)
    {
        $query = User::query()->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        $roleCounts = [
            'total' => User::count(),
            'admin' => User::where('role', User::ROLE_ADMIN)->count(),
            'waka_kurikulum' => User::where('role', User::ROLE_WAKA_KURIKULUM)->count(),
            'wali_kelas' => User::where('role', User::ROLE_WALI_KELAS)->count(),
            'guru' => User::where('role', User::ROLE_GURU)->count(),
            'siswa' => User::where('role', User::ROLE_SISWA)->count(),
        ];

        return view('pages.admin.users.index', [
            'title' => 'Manajemen Akun Pengguna - E-Rapor',
            'users' => $users,
            'roleCounts' => $roleCounts,
        ]);
    }

    /**
     * Simpan akun user baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'role' => 'required|string|in:admin,waka_kurikulum,wali_kelas,guru,siswa',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil ditambahkan.');
    }

    /**
     * Update akun user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|string|in:admin,waka_kurikulum,wali_kelas,guru,siswa',
            'is_active' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $user->is_active,
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Reset password akun
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', "Password untuk akun {$user->name} berhasil direset.");
    }

    /**
     * Hapus akun pengguna
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Akun {$userName} berhasil dihapus.");
    }
}

