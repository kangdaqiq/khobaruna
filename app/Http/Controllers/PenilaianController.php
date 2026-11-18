<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruMapelKelas;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\School;
use App\Models\Siswa;
use App\Models\User;
use App\Services\ExamSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenilaianController extends Controller
{
    /**
     * Halaman Utama Penilaian: Daftar Rombel & Mapel yang Diampu oleh Guru yang Sedang Login
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? User::ROLE_ADMIN;
        $guru = $user?->guru;
        $mode = $request->input('mode'); // 'mengajar' atau null (supervisi)

        // Tentukan mode Guru Mapel:
        // - Role Guru biasa atau Wali Kelas otomatis mode guru mapel
        // - Role Waka Kurikulum jika mengakses ?mode=mengajar
        $isGuruMode = ($role === User::ROLE_GURU) ||
                      ($role === User::ROLE_WALI_KELAS) ||
                      ($role === User::ROLE_WAKA_KURIKULUM && $mode === 'mengajar');

        // Query penugasan mengajar
        $query = GuruMapelKelas::with(['guru', 'mapel', 'kelas.siswas']);

        if ($isGuruMode) {
            // Guru & Wali Kelas (atau Waka Kurikulum saat membuka Kelas Saya)
            // HANYA menampilkan kelas dan mapel yang diampunya saja!
            if (!$guru) {
                return view('pages.penilaian.index', [
                    'assignments' => collect(),
                    'isGuru' => true,
                    'isGuruMode' => true,
                    'isWaka' => $role === User::ROLE_WAKA_KURIKULUM,
                    'hasGuruProfile' => false,
                    'mode' => $mode,
                    'guru' => null,
                    'gurus' => collect(),
                    'kelases' => collect(),
                    'mapels' => collect(),
                    'error' => 'Data profil guru Anda belum terhubung dengan akun login.',
                ]);
            }
            $query->where('guru_id', $guru->id);
        } else {
            // Admin atau Waka Kurikulum (Supervisi seluruh rombel)
            if ($request->filled('guru_id')) {
                $query->where('guru_id', $request->guru_id);
            }
            if ($request->filled('kelas_id')) {
                $query->where('kelas_id', $request->kelas_id);
            }
            if ($request->filled('mapel_id')) {
                $query->where('mapel_id', $request->mapel_id);
            }
        }

        $school = School::first();
        $activeTahunAjaran = $school->tahun_ajaran ?? '2025/2026';
        $activeSemester = $school->semester ?? '2';
        $semesterVariants = ($activeSemester == '2' || strtolower($activeSemester) === 'genap') ? ['2', 'Genap'] : ['1', 'Ganjil'];

        $assignments = $query->get()->map(function ($assign) use ($activeTahunAjaran, $semesterVariants) {
            $totalSiswa = $assign->kelas?->siswas->count() ?? 0;
            $filledCount = Nilai::where('kelas_id', $assign->kelas_id)
                ->where('mapel_id', $assign->mapel_id)
                ->where('tahun_ajaran', $activeTahunAjaran)
                ->whereIn('semester', $semesterVariants)
                ->whereNotNull('nilai_akhir')
                ->count();

            $assign->total_siswa = $totalSiswa;
            $assign->filled_count = $filledCount;
            $assign->progress = $totalSiswa > 0 ? round(($filledCount / $totalSiswa) * 100) : 0;
            return $assign;
        });

        $gurus = !$isGuruMode ? Guru::orderBy('nama')->get() : collect();
        $kelases = !$isGuruMode ? Kelas::orderBy('nama_kelas')->get() : collect();
        $mapels = !$isGuruMode ? Mapel::orderBy('nama_mapel')->get() : collect();

        return view('pages.penilaian.index', [
            'assignments' => $assignments,
            'isGuru' => $isGuruMode,
            'isGuruMode' => $isGuruMode,
            'isWaka' => $role === User::ROLE_WAKA_KURIKULUM,
            'hasGuruProfile' => (bool) $guru,
            'mode' => $mode,
            'guru' => $guru,
            'gurus' => $gurus,
            'kelases' => $kelases,
            'mapels' => $mapels,
            'activeTahunAjaran' => $activeTahunAjaran,
            'activeSemester' => $activeSemester,
        ]);
    }

    /**
     * Lembar Penilaian Siswa per Kelas dan Mapel
     */
    public function input(Kelas $kelas, Mapel $mapel)
    {
        $this->authorizeTeacherForClassAndMapel($kelas, $mapel);

        $school = School::first();
        $activeTahunAjaran = $school->tahun_ajaran ?? '2025/2026';
        $activeSemester = $school->semester ?? '2';
        $semesterVariants = ($activeSemester == '2' || strtolower($activeSemester) === 'genap') ? ['2', 'Genap'] : ['1', 'Ganjil'];

        $siswas = $kelas->siswas()->orderBy('nama')->get();
        $nilais = Nilai::where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->whereIn('semester', $semesterVariants)
            ->get()
            ->keyBy('siswa_id');

        $assignedGuru = GuruMapelKelas::with('guru')
            ->where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->first();

        // Ambil daftar Tujuan Pembelajaran (TP) untuk mapel & tingkat ini
        $tps = \App\Models\TujuanPembelajaran::where('mapel_id', $mapel->id)
            ->where('tingkat', $kelas->tingkat)
            ->orderBy('urutan')
            ->get();

        // Jumlah sumatif harian otomatis tergantung jumlah TP yang dimasukkan guru (1 s/d 6 TP)
        $tpCount = $tps->count();
        if ($tpCount > 0) {
            $jumlahSumatif = min(6, max(1, $tpCount));
        } else {
            $jumlahSumatif = $assignedGuru?->jumlah_sumatif ?: 3;
        }

        $bobotHarian = $assignedGuru?->bobot_harian ?: 60;
        $bobotSas = $assignedGuru?->bobot_sas ?: 40;

        return view('pages.penilaian.input', compact(
            'kelas',
            'mapel',
            'siswas',
            'nilais',
            'assignedGuru',
            'jumlahSumatif',
            'tpCount',
            'bobotHarian',
            'bobotSas',
            'tps',
            'activeTahunAjaran',
            'activeSemester'
        ));
    }

    /**
     * Simpan / Perbarui Nilai Siswa (Sumatif 1-6, Nilai SAS, Rasio Custom, Nilai Akhir)
     */
    public function store(Request $request, Kelas $kelas, Mapel $mapel)
    {
        $this->authorizeTeacherForClassAndMapel($kelas, $mapel);

        $user = Auth::user();
        $guruId = $user->guru?->id;

        $assigned = GuruMapelKelas::where('kelas_id', $kelas->id)->where('mapel_id', $mapel->id)->first();
        if (!$guruId) {
            $guruId = $assigned?->guru_id ?? 1;
        }

        // Simpan setting rasio custom & jumlah sumatif untuk kelas-mapel ini
        $jumlahSumatif = max(1, min(6, (int) $request->input('jumlah_sumatif', 3)));
        $bobotHarian = (int) $request->input('bobot_harian', 60);
        $bobotSas = (int) $request->input('bobot_sas', 40);

        if ($assigned) {
            $assigned->update([
                'jumlah_sumatif' => $jumlahSumatif,
                'bobot_harian' => $bobotHarian,
                'bobot_sas' => $bobotSas,
            ]);
        }

        $school = School::first();
        $activeTahunAjaran = $school->tahun_ajaran ?? '2025/2026';
        $activeSemester = $school->semester ?? '2';

        $grades = $request->input('nilai', []);
        $savedCount = 0;

        foreach ($grades as $siswaId => $row) {
            // Ambil nilai sumatif 1 s/d 6
            $sumatifs = [];
            $validSumatifs = [];

            for ($i = 1; $i <= 6; $i++) {
                $val = (isset($row["sumatif_{$i}"]) && $row["sumatif_{$i}"] !== '') ? (float) $row["sumatif_{$i}"] : null;
                $sumatifs["sumatif_{$i}"] = $val;

                if ($i <= $jumlahSumatif && $val !== null) {
                    $validSumatifs[] = $val;
                }
            }

            // Hitung Rata-rata Sumatif Harian (RSLM)
            $rataHarian = null;
            if (count($validSumatifs) > 0) {
                $rataHarian = round(array_sum($validSumatifs) / count($validSumatifs), 1);
            }

            // Nilai SAS (UAS)
            $sas = (isset($row['uas']) && $row['uas'] !== '') ? (float) $row['uas'] : null;

            // Hitung Nilai Akhir berdasarkan Rasio Custom Guru
            $nilaiAkhir = null;
            $totalBobot = $bobotHarian + $bobotSas;

            if ($totalBobot > 0) {
                if ($rataHarian !== null && $sas !== null) {
                    $nilaiAkhir = round((($rataHarian * $bobotHarian) + ($sas * $bobotSas)) / $totalBobot, 1);
                } elseif ($rataHarian !== null && $sas === null) {
                    $nilaiAkhir = $rataHarian;
                } elseif ($rataHarian === null && $sas !== null) {
                    $nilaiAkhir = $sas;
                }
            }

            Nilai::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'kelas_id' => $kelas->id,
                    'mapel_id' => $mapel->id,
                    'tahun_ajaran' => $activeTahunAjaran,
                    'semester' => $activeSemester,
                ],
                [
                    'school_id' => $kelas->school_id,
                    'guru_id' => $guruId,
                    'sumatif_1' => $sumatifs['sumatif_1'],
                    'sumatif_2' => $sumatifs['sumatif_2'],
                    'sumatif_3' => $sumatifs['sumatif_3'],
                    'sumatif_4' => $sumatifs['sumatif_4'],
                    'sumatif_5' => $sumatifs['sumatif_5'],
                    'sumatif_6' => $sumatifs['sumatif_6'],
                    'rata_sumatif_harian' => $rataHarian,
                    'nilai_tugas' => $rataHarian, // kompatibilitas kolom legacy tugas
                    'nilai_uas' => $sas,
                    'nilai_akhir' => $nilaiAkhir,
                    'capaian_kompetensi' => !empty($row['capaian']) ? trim($row['capaian']) : null,
                ]
            );
            $savedCount++;
        }

        return redirect()->route('penilaian.input', [$kelas->id, $mapel->id])
            ->with('success', "Berhasil menyimpan penilaian untuk {$savedCount} siswa di kelas {$kelas->nama_kelas} (Tahun: {$activeTahunAjaran}, Semester: {$activeSemester}) dengan rasio {$bobotHarian}:{$bobotSas}.");
    }

    /**
     * Tarik Nilai SAS dari Exam Web untuk Rombel dan Mapel Ini
     */
    public function syncSas(Kelas $kelas, Mapel $mapel, ExamSyncService $examSyncService)
    {
        $this->authorizeTeacherForClassAndMapel($kelas, $mapel);

        $school = School::first();
        $activeTahunAjaran = $school->tahun_ajaran ?? '2025/2026';
        $activeSemester = $school->semester ?? '2';

        $result = $examSyncService->syncSasForClassSubject($kelas->id, $mapel->id, $activeTahunAjaran, $activeSemester);

        if ($result['success']) {
            return redirect()->route('penilaian.input', [$kelas->id, $mapel->id])
                ->with('success', $result['message']);
        }

        return redirect()->route('penilaian.input', [$kelas->id, $mapel->id])
            ->with('error', $result['message']);
    }

    /**
     * Otorisasi Ketat: Hanya Guru yang Mengampu Mapel & Kelas Ini yang Boleh Menilai!
     */
    protected function authorizeTeacherForClassAndMapel(Kelas $kelas, Mapel $mapel): void
    {
        $user = Auth::user();

        // Admin dan Waka Kurikulum memiliki hak supervisi penuh
        if ($user && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_WAKA_KURIKULUM])) {
            return;
        }

        // Jika Guru biasa atau Wali Kelas, validasi kepemilikan mengajar di guru_mapel_kelas
        if ($user && in_array($user->role, [User::ROLE_GURU, User::ROLE_WALI_KELAS])) {
            $guru = $user->guru;

            if (!$guru) {
                abort(403, 'Akses Ditolak: Akun Anda belum ditautkan dengan data Guru.');
            }

            $isAssigned = GuruMapelKelas::where('guru_id', $guru->id)
                ->where('kelas_id', $kelas->id)
                ->where('mapel_id', $mapel->id)
                ->exists();

            if (!$isAssigned) {
                abort(403, "Akses Ditolak: Anda bukan guru pengampu mata pelajaran [{$mapel->nama_mapel}] di kelas [{$kelas->nama_kelas}]. Anda hanya dapat menilai mata pelajaran dan kelas yang Anda ampu saja.");
            }

            return;
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengisi penilaian siswa.');
    }
}
