<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\School;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    /**
     * Tampilkan data siswa
     */
    public function index(Request $request)
    {
        $query = Siswa::with(['user', 'kelas'])->latest();

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('kelas_nama', 'like', "%{$search}%");
            });
        }

        $siswas = $query->paginate(15)->withQueryString();
        $kelases = Kelas::orderBy('nama_kelas')->get();

        $stats = [
            'total' => Siswa::count(),
            'has_class' => Siswa::whereNotNull('kelas_id')->count(),
            'has_account' => Siswa::whereNotNull('user_id')->count(),
        ];

        return view('pages.admin.siswas.index', [
            'title' => 'Data Siswa & Peserta Didik - E-Rapor',
            'siswas' => $siswas,
            'kelases' => $kelases,
            'stats' => $stats,
        ]);
    }

    /**
     * Simpan data siswa baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:siswas,nis',
            'nisn' => 'nullable|string|max:50',
            'kelas_id' => 'nullable|exists:kelases,id',
            'tgl_lahir' => 'nullable|string|max:50',
            'jenis_kelamin' => 'nullable|string|in:Laki-laki,Perempuan',
            'no_wa' => 'nullable|string|max:20',
            'wa_ortu' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($validated) {
            $school = School::first();
            $kelas = !empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;

            $username = $validated['nis'];
            $email = "{$username}@siswa.local";
            $defaultPass = !empty($validated['tgl_lahir']) ? str_replace('-', '', $validated['tgl_lahir']) : 'siswa123';

            $user = User::create([
                'name' => $validated['nama'],
                'username' => $username,
                'email' => $email,
                'role' => User::ROLE_SISWA,
                'password' => Hash::make($validated['password'] ?? $defaultPass),
                'is_active' => true,
            ]);

            Siswa::create([
                'user_id' => $user->id,
                'school_id' => $school?->id,
                'kelas_id' => $kelas?->id,
                'kelas_nama' => $kelas?->nama_kelas,
                'nama' => $validated['nama'],
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?? null,
                'tgl_lahir' => $validated['tgl_lahir'] ?? null,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
                'no_wa' => $validated['no_wa'] ?? null,
                'wa_ortu' => $validated['wa_ortu'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
            ]);

            if ($kelas) {
                $kelas->increment('jumlah_siswa');
            }
        });

        return redirect()->route('admin.siswas.index')->with('success', 'Data siswa dan akun login berhasil ditambahkan.');
    }

    /**
     * Update data siswa
     */
    public function update(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:siswas,nis,' . $siswa->id,
            'nisn' => 'nullable|string|max:50',
            'kelas_id' => 'nullable|exists:kelases,id',
            'tgl_lahir' => 'nullable|string|max:50',
            'jenis_kelamin' => 'nullable|string|in:Laki-laki,Perempuan',
            'no_wa' => 'nullable|string|max:20',
            'wa_ortu' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
        ]);

        $kelas = !empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;

        $siswa->update([
            'nama' => $validated['nama'],
            'nis' => $validated['nis'],
            'nisn' => $validated['nisn'] ?? null,
            'kelas_id' => $kelas?->id,
            'kelas_nama' => $kelas?->nama_kelas,
            'tgl_lahir' => $validated['tgl_lahir'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'no_wa' => $validated['no_wa'] ?? null,
            'wa_ortu' => $validated['wa_ortu'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ]);

        if ($siswa->user) {
            $siswa->user->update([
                'name' => $validated['nama'],
                'username' => $validated['nis'],
            ]);
        }

        return redirect()->route('admin.siswas.index')->with('success', "Data siswa {$siswa->nama} berhasil diperbarui.");
    }

    /**
     * Hapus data siswa
     */
    public function destroy($id)
    {
        $siswa = Siswa::findOrFail($id);
        $nama = $siswa->nama;

        if ($siswa->kelas_id) {
            Kelas::where('id', $siswa->kelas_id)->where('jumlah_siswa', '>', 0)->decrement('jumlah_siswa');
        }

        if ($siswa->user) {
            $siswa->user->delete();
        }

        $siswa->delete();

        return redirect()->route('admin.siswas.index')->with('success', "Data siswa {$nama} berhasil dihapus.");
    }
}

