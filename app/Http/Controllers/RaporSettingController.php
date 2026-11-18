<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RaporSettingController extends Controller
{
    /**
     * Halaman Pengaturan Rapor & Tanda Tangan
     */
    public function index()
    {
        $school = School::first();
        if (!$school) {
            return redirect()->route('admin.sync.index')->with('error', 'Silakan lakukan sinkronisasi data sekolah terlebih dahulu.');
        }

        // Ambil seluruh guru yang menjadi wali kelas
        $waliKelasGurus = Guru::whereHas('kelasWali')
            ->orWhereIn('id', Kelas::whereNotNull('wali_kelas_id')->pluck('wali_kelas_id'))
            ->orWhere('is_wali_kelas', true)
            ->with('kelasWali')
            ->orderBy('nama')
            ->get();

        // Jika belum ada yang ditandai, ambil semua kelas dan gurunya
        if ($waliKelasGurus->isEmpty()) {
            $waliKelasGurus = Guru::whereIn('nama', Kelas::whereNotNull('wali_kelas_nama')->pluck('wali_kelas_nama'))
                ->orderBy('nama')
                ->get();
        }

        return view('pages.admin.rapor-setting.index', [
            'title' => 'Pengaturan Cetak Rapor & Tanda Tangan',
            'school' => $school,
            'waliKelasGurus' => $waliKelasGurus,
        ]);
    }

    /**
     * Update Pengaturan Titimangsa, Kepala Sekolah, & TTD/Stempel Sekolah
     */
    public function update(Request $request)
    {
        $school = School::firstOrFail();

        $validated = $request->validate([
            'tahun_ajaran' => 'required|string|max:20',
            'semester' => 'required|in:1,2,Ganjil,Genap',
            'titimangsa_tempat' => 'required|string|max:100',
            'titimangsa_tanggal' => 'required|date',
            'headmaster_name' => 'required|string|max:150',
            'headmaster_nip' => 'nullable|string|max:50',
            'headmaster_signature' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'school_stamp' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $payload = [
            'tahun_ajaran' => $validated['tahun_ajaran'],
            'semester' => $validated['semester'],
            'titimangsa_tempat' => $validated['titimangsa_tempat'],
            'titimangsa_tanggal' => $validated['titimangsa_tanggal'],
            'headmaster_name' => $validated['headmaster_name'],
            'headmaster_nip' => $validated['headmaster_nip'] ?? '-',
        ];

        // Upload TTD Kepala Sekolah
        if ($request->hasFile('headmaster_signature')) {
            $file = $request->file('headmaster_signature');
            $fileName = 'ttd_kepsek_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('signatures', $fileName, 'public');
            $payload['headmaster_signature'] = '/storage/' . $path;
        }

        // Upload Stempel Sekolah
        if ($request->hasFile('school_stamp')) {
            $file = $request->file('school_stamp');
            $fileName = 'stempel_sekolah_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('stamps', $fileName, 'public');
            $payload['school_stamp'] = '/storage/' . $path;
        }

        $school->update($payload);

        return back()->with('success', 'Pengaturan titimangsa, kepala sekolah, dan tanda tangan berhasil disimpan.');
    }

    /**
     * Upload Tanda Tangan Wali Kelas
     */
    public function uploadWalas(Request $request, $guruId)
    {
        $guru = Guru::findOrFail($guruId);

        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $file = $request->file('signature');
        $fileName = 'ttd_walas_' . $guru->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('signatures', $fileName, 'public');

        $guru->update([
            'signature' => '/storage/' . $path,
        ]);

        return back()->with('success', "Tanda tangan untuk Wali Kelas {$guru->nama} berhasil diunggah.");
    }

    /**
     * Hapus Tanda Tangan Wali Kelas
     */
    public function deleteWalasSignature($guruId)
    {
        $guru = Guru::findOrFail($guruId);
        $guru->update(['signature' => null]);
        return back()->with('success', "Tanda tangan Wali Kelas {$guru->nama} berhasil dihapus.");
    }

    /**
     * Hapus Tanda Tangan Tertentu
     */
    public function removeSignature(Request $request, $type, $id = null)
    {
        $school = School::firstOrFail();

        if ($type === 'kepsek') {
            $school->update(['headmaster_signature' => null]);
            return back()->with('success', 'Tanda tangan Kepala Sekolah berhasil dihapus.');
        } elseif ($type === 'stempel') {
            $school->update(['school_stamp' => null]);
            return back()->with('success', 'Stempel Sekolah berhasil dihapus.');
        } elseif ($type === 'walas' && $id) {
            $guru = Guru::findOrFail($id);
            $guru->update(['signature' => null]);
            return back()->with('success', "Tanda tangan Wali Kelas {$guru->nama} berhasil dihapus.");
        }

        return back();
    }
}
