<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    /**
     * Tampilkan data guru
     */
    public function index(Request $request)
    {
        $query = Guru::with(['user', 'kelasWali'])->latest();

        if ($request->filled('wali_kelas')) {
            $query->where('is_wali_kelas', $request->boolean('wali_kelas'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        $gurus = $query->paginate(15)->withQueryString();
        $kelases = Kelas::all();

        $stats = [
            'total' => Guru::count(),
            'wali_kelas' => Guru::where('is_wali_kelas', true)->count(),
            'non_wali' => Guru::where('is_wali_kelas', false)->count(),
            'has_account' => Guru::whereNotNull('user_id')->count(),
        ];

        return view('pages.admin.gurus.index', [
            'title' => 'Data Guru & Tenaga Pendidik - E-Rapor',
            'gurus' => $gurus,
            'kelases' => $kelases,
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan data guru baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'no_wa' => 'nullable|string|max:20',
            'is_wali_kelas' => 'nullable|boolean',
            'kelas_id' => 'nullable|exists:kelases,id',
            'password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $school = School::first();
            $isWaliKelas = $request->boolean('is_wali_kelas');
            $role = $isWaliKelas ? User::ROLE_WALI_KELAS : User::ROLE_GURU;

            // Generate username dari NIP atau nama
            $username = !empty($validated['nip']) ? $validated['nip'] : 'guru_' . time();
            $email = !empty($validated['email']) ? $validated['email'] : "{$username}@erapor.local";

            $user = User::create([
                'name' => $validated['nama'],
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'password' => Hash::make($validated['password'] ?? 'guru123'),
                'is_active' => true,
            ]);

            $guru = Guru::create([
                'user_id' => $user->id,
                'school_id' => $school?->id,
                'nama' => $validated['nama'],
                'nip' => $validated['nip'] ?? null,
                'email' => $email,
                'no_wa' => $validated['no_wa'] ?? null,
                'is_wali_kelas' => $isWaliKelas,
                'kelas_id' => $validated['kelas_id'] ?? null,
            ]);

            if ($isWaliKelas && !empty($validated['kelas_id'])) {
                Kelas::where('id', $validated['kelas_id'])->update([
                    'wali_kelas_id' => $guru->id,
                    'wali_kelas_nama' => $guru->nama,
                ]);
            }
        });

        return redirect()->route('admin.gurus.index')->with('success', 'Data guru dan akun login berhasil ditambahkan.');
    }

    /**
     * Update data guru
     */
    public function update(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'no_wa' => 'nullable|string|max:20',
            'is_wali_kelas' => 'nullable|boolean',
            'kelas_id' => 'nullable|exists:kelases,id',
        ]);

        $isWaliKelas = $request->boolean('is_wali_kelas');

        $guru->update([
            'nama' => $validated['nama'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'] ?? null,
            'no_wa' => $validated['no_wa'] ?? null,
            'is_wali_kelas' => $isWaliKelas,
            'kelas_id' => $validated['kelas_id'] ?? null,
        ]);

        if ($guru->user) {
            $guru->user->update([
                'name' => $validated['nama'],
                'email' => $validated['email'] ?? $guru->user->email,
                'role' => $isWaliKelas ? User::ROLE_WALI_KELAS : User::ROLE_GURU,
            ]);
        }

        if ($isWaliKelas && !empty($validated['kelas_id'])) {
            Kelas::where('id', $validated['kelas_id'])->update([
                'wali_kelas_id' => $guru->id,
                'wali_kelas_nama' => $guru->nama,
            ]);
        }

        return redirect()->route('admin.gurus.index')->with('success', "Data guru {$guru->nama} berhasil diperbarui.");
    }

    /**
     * Hapus data guru
     */
    public function destroy($id)
    {
        $guru = Guru::findOrFail($id);
        $nama = $guru->nama;

        if ($guru->user) {
            $guru->user->delete();
        }

        $guru->delete();

        return redirect()->route('admin.gurus.index')->with('success', "Data guru {$nama} berhasil dihapus.");
    }
}

