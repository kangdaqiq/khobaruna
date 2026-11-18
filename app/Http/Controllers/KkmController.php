<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KkmController extends Controller
{
    /**
     * Halaman Pengaturan Batas Ketercapaian / KKM Tiap Kelas oleh Admin & Waka Kurikulum
     */
    public function index()
    {
        $user = Auth::user();
        if ($user && !in_array($user->role, [User::ROLE_ADMIN, User::ROLE_WAKA_KURIKULUM])) {
            abort(403, 'Akses Ditolak: Hanya Administrator dan Waka Kurikulum yang berhak mengatur batas ketercapaian (KKM) kelas.');
        }

        $school = School::first();
        $kelases = Kelas::with('waliKelas')->orderBy('tingkat')->orderBy('nama_kelas')->get();

        return view('pages.admin.kkm.index', compact('kelases', 'school'));
    }

    /**
     * Simpan / Perbarui KKM Seluruh Kelas (Tiap Kelas Berbeda-beda)
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if ($user && !in_array($user->role, [User::ROLE_ADMIN, User::ROLE_WAKA_KURIKULUM])) {
            abort(403, 'Akses Ditolak: Hanya Administrator dan Waka Kurikulum yang berhak mengatur batas ketercapaian (KKM) kelas.');
        }

        $kkmData = $request->input('kkm', []);

        foreach ($kkmData as $kelasId => $val) {
            $kkmVal = max(50, min(100, (int) $val));
            Kelas::where('id', $kelasId)->update(['kkm' => $kkmVal]);
        }

        return redirect()->route('admin.kkm.index')
            ->with('success', 'Batas ketercapaian (KKM) untuk seluruh kelas berhasil diperbarui.');
    }
}

