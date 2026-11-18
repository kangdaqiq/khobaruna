<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\School;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AbsensiSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbsensiController extends Controller
{
    /**
     * Dapatkan otorisasi dan daftar kelas yang dapat diakses pengguna
     * - Admin & Waka Kurikulum: Seluruh Rombel
     * - Walas (Wali Kelas): HANYA Rombel Kelas Binaan Sendiri
     * - Guru Mapel: TIDAK DIBERI AKSES SAMA SEKALI (403 Forbidden)
     */
    private function getAuthorizedUserContext(): array
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $isAdminOrWaka = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_WAKA_KURIKULUM]);

        $guru = $user->guru ?: Guru::where('user_id', $user->id)->first();
        if (!$guru && $user->email) {
            $guru = Guru::where('email', $user->email)->first();
        }

        $waliKelasRecords = collect();
        if ($guru) {
            $waliKelasRecords = Kelas::where('wali_kelas_id', $guru->id)
                ->orWhere('wali_kelas_nama', $guru->nama)
                ->get();
        }

        $isWaliKelas = ($user->role === User::ROLE_WALI_KELAS) || $waliKelasRecords->isNotEmpty();

        // Otorisasi Ketat: Guru Mapel murni DITOLAK total!
        if (!$isAdminOrWaka && !$isWaliKelas) {
            abort(403, 'Akses ditolak: Guru mata pelajaran tidak memiliki akses ke rekapitulasi absensi siswa. Fitur ini hanya dapat diakses oleh Wali Kelas, Waka Kurikulum, dan Administrator.');
        }

        return [
            'user' => $user,
            'guru' => $guru,
            'isAdminOrWaka' => $isAdminOrWaka,
            'isWaliKelas' => $isWaliKelas && !$isAdminOrWaka, // Murni dalam mode Wali Kelas
            'waliKelasRecords' => $waliKelasRecords,
            'waliKelasIds' => $waliKelasRecords->pluck('id')->all(),
        ];
    }

    /**
     * Tampilkan Rekapitulasi Absensi Siswa & Panel Sinkronisasi Semester
     */
    public function index(Request $request, AbsensiSyncService $syncService)
    {
        $school = School::first();
        if (!$school) {
            return redirect()->route('admin.sync.index')->with('error', 'Silakan lakukan sinkronisasi data sekolah master terlebih dahulu.');
        }

        $ctx = $this->getAuthorizedUserContext();

        $activeSem = $school->semester ?? '2';
        $defaultSemester = ($activeSem == '2' || strtolower($activeSem) === 'genap') ? 'Genap' : 'Ganjil';
        $tahunAjaran = $request->input('tahun_ajaran', $school->tahun_ajaran ?? '2025/2026');
        $semester = $request->input('semester', $defaultSemester);
        $selectedKelasId = $request->input('kelas_id');
        $search = $request->input('q');

        // Cek status koneksi backend web absen via API
        $apiStatus = $syncService->checkApiConnection();

        // Inisialisasi query rekap absensi
        $query = AbsensiSiswa::with(['siswa', 'kelas'])
            ->where('school_id', $school->id)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester);

        // Batasi kelas sesuai peran (Wali Kelas vs Admin/Waka)
        if ($ctx['isWaliKelas']) {
            if (empty($ctx['waliKelasIds'])) {
                abort(403, 'Akses ditolak: Anda terdaftar sebagai wali kelas namun belum ada rombel kelas yang ditugaskan kepada Anda.');
            }

            $kelases = $ctx['waliKelasRecords'];

            // Validasi jika user memilih kelas_id lewat URL
            if ($selectedKelasId && !in_array($selectedKelasId, $ctx['waliKelasIds'])) {
                abort(403, 'Akses ditolak: Anda hanya dapat mengakses data rekap absensi untuk kelas yang Anda walikan.');
            }

            // Default ke kelas wali kelas pertama jika belum dipilih
            if (!$selectedKelasId) {
                $selectedKelasId = $ctx['waliKelasIds'][0];
            }

            $query->where('kelas_id', $selectedKelasId);
        } else {
            // Admin & Waka Kurikulum: Bebas pilih semua kelas
            $kelases = Kelas::where('school_id', $school->id)->orderBy('nama_kelas')->get();
            if ($selectedKelasId) {
                $query->where('kelas_id', $selectedKelasId);
            }
        }

        if ($search) {
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        // Ambil data agregat sebelum pagination
        $stats = [
            'total_siswa' => (clone $query)->count(),
            'total_masuk' => (clone $query)->sum('masuk'),
            'total_sakit' => (clone $query)->sum('sakit'),
            'total_izin' => (clone $query)->sum('izin'),
            'total_alpha' => (clone $query)->sum('alpha'),
            'total_bolos' => (clone $query)->sum('bolos'),
            'avg_kehadiran' => round((clone $query)->avg('persentase') ?? 0, 1),
        ];

        // Tanggal rentang aktif saat ini jika sudah pernah disinkronkan
        $latestRecord = (clone $query)->whereNotNull('start_date')->first();
        $defaultStartDate = $latestRecord?->start_date?->format('Y-m-d') ?: date('Y-07-01');
        $defaultEndDate = $latestRecord?->end_date?->format('Y-m-d') ?: date('Y-12-31');

        $rekapList = $query->orderBy('kelas_id')->orderBy('id')->paginate(50)->withQueryString();

        return view('pages.admin.absensi.index', [
            'title' => 'Rekapitulasi Absensi Siswa - E-Rapor',
            'school' => $school,
            'kelases' => $kelases,
            'selectedKelasId' => $selectedKelasId,
            'tahunAjaran' => $tahunAjaran,
            'semester' => $semester,
            'search' => $search,
            'apiStatus' => $apiStatus,
            'stats' => $stats,
            'defaultStartDate' => $defaultStartDate,
            'defaultEndDate' => $defaultEndDate,
            'rekapList' => $rekapList,
            'isWaliKelas' => $ctx['isWaliKelas'],
            'waliKelasRecords' => $ctx['waliKelasRecords'],
            'currentUser' => $ctx['user'],
            'currentGuru' => $ctx['guru'],
        ]);
    }

    /**
     * Jalankan Sinkronisasi Rekap Absensi dari Backend Web Absen
     */
    public function sync(Request $request, AbsensiSyncService $syncService)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'tahun_ajaran' => 'nullable|string',
            'semester' => 'nullable|string',
            'kelas_id' => 'nullable',
        ]);

        $ctx = $this->getAuthorizedUserContext();

        $requestedKelasId = $request->input('kelas_id');

        // Walas hanya dapat menyinkronkan data untuk kelas binaannya
        if ($ctx['isWaliKelas']) {
            if (empty($ctx['waliKelasIds'])) {
                abort(403, 'Akses ditolak: Anda belum memiliki rombel binaan wali kelas.');
            }

            if ($requestedKelasId && !in_array($requestedKelasId, $ctx['waliKelasIds'])) {
                abort(403, 'Akses ditolak: Anda hanya dapat menyinkronkan data absensi untuk kelas yang Anda walikan.');
            }

            $targetKelasId = $requestedKelasId ?: $ctx['waliKelasIds'][0];
        } else {
            $targetKelasId = $requestedKelasId;
        }

        $school = School::first();
        $activeSem = $school?->semester ?? '2';
        $defaultSemester = ($activeSem == '2' || strtolower($activeSem) === 'genap') ? 'Genap' : 'Ganjil';

        $result = $syncService->syncRekap([
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'tahun_ajaran' => $request->input('tahun_ajaran', $school?->tahun_ajaran ?? '2025/2026'),
            'semester' => $request->input('semester', $defaultSemester),
            'kelas_id' => $targetKelasId,
        ]);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Update Manual Rekap Absensi Siswa (Koreksi)
     */
    public function update(Request $request, $id)
    {
        $ctx = $this->getAuthorizedUserContext();
        $rekap = AbsensiSiswa::findOrFail($id);

        // Walas hanya boleh mengoreksi siswa di kelasnya sendiri
        if ($ctx['isWaliKelas']) {
            if (!in_array($rekap->kelas_id, $ctx['waliKelasIds'])) {
                abort(403, 'Akses ditolak: Anda hanya dapat mengoreksi data absensi untuk siswa di kelas binaan Anda.');
            }
        }

        $validated = $request->validate([
            'masuk' => 'required|integer|min:0',
            'sakit' => 'required|integer|min:0',
            'izin' => 'required|integer|min:0',
            'alpha' => 'required|integer|min:0',
            'bolos' => 'required|integer|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        $totalHari = $validated['masuk'] + $validated['sakit'] + $validated['izin'] + $validated['alpha'] + $validated['bolos'];
        $persentase = $totalHari > 0 ? round(($validated['masuk'] / $totalHari) * 100, 2) : 0.00;

        $rekap->update([
            'masuk' => $validated['masuk'],
            'sakit' => $validated['sakit'],
            'izin' => $validated['izin'],
            'alpha' => $validated['alpha'],
            'bolos' => $validated['bolos'],
            'total_hari' => $totalHari,
            'persentase' => $persentase,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return back()->with('success', "Rekap absensi {$rekap->siswa?->nama} berhasil diperbarui.");
    }
}
