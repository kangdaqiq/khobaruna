<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\School;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RaporController extends Controller
{
    /**
     * Dashboard / Halaman Pemilihan Cetak Rapor
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isWalas = $user->isWaliKelas();
        $isAdminOrWaka = $user->isAdmin() || $user->isWaka();

        // Cek otorisasi: Guru mapel murni tanpa tugas walas dilarang akses
        if (!$isAdminOrWaka && !$isWalas) {
            return redirect()->route('dashboard')->with('error', 'Akses cetak rapor hanya diperuntukkan bagi Wali Kelas, Waka Kurikulum, dan Administrator.');
        }

        $school = School::first();
        $currentSemester = $request->query('semester', $school->semester ?? '2');
        $currentTahunAjaran = $request->query('tahun_ajaran', $school->tahun_ajaran ?? '2025/2026');
        $semesterVariants = ($currentSemester == '2' || strtolower($currentSemester) === 'genap') ? ['2', 'Genap'] : ['1', 'Ganjil'];

        // Ambil daftar kelas yang bisa diakses
        if ($isWalas && !$isAdminOrWaka) {
            $kelasList = $user->guru ? $user->guru->kelasWali : collect();
            if ($kelasList->isEmpty() && $user->guru) {
                $kelasList = Kelas::where('wali_kelas_id', $user->guru->id)
                    ->orWhere('wali_kelas_nama', $user->guru->nama)
                    ->get();
            }
        } else {
            $kelasList = Kelas::with('waliKelas')->orderBy('tingkat')->orderBy('nama_kelas')->get();
        }

        // Tentukan kelas aktif
        $selectedKelasId = $request->query('kelas_id', $kelasList->first()?->id);
        $selectedKelas = $kelasList->firstWhere('id', $selectedKelasId) ?? $kelasList->first();

        $siswas = collect();
        $summary = [
            'total_siswa' => 0,
            'mapel_count' => 0,
            'completed_grades' => 0,
        ];

        if ($selectedKelas) {
            // Ambil siswa di kelas ini beserta nilai dan absensi
            $siswas = Siswa::where('kelas_id', $selectedKelas->id)
                ->orderBy('nama')
                ->get();

            $summary['total_siswa'] = $siswas->count();

            // Hitung mapel unik yang diajarkan di kelas ini
            $mapelIds = $selectedKelas->guruMapelKelas()->pluck('mapel_id')->unique();
            $summary['mapel_count'] = $mapelIds->count();

            // Load absensi untuk siswa di kelas ini khusus semester & tahun ajaran yang dipilih
            $absensiMap = AbsensiSiswa::whereIn('siswa_id', $siswas->pluck('id'))
                ->where('tahun_ajaran', $currentTahunAjaran)
                ->whereIn('semester', $semesterVariants)
                ->get()
                ->keyBy('siswa_id');

            // Load penilaian untuk rekap khusus semester & tahun ajaran yang dipilih
            $penilaianMap = Nilai::whereIn('siswa_id', $siswas->pluck('id'))
                ->where('tahun_ajaran', $currentTahunAjaran)
                ->whereIn('semester', $semesterVariants)
                ->get()
                ->groupBy('siswa_id');

            foreach ($siswas as $siswa) {
                $siswa->absensi = $absensiMap->get($siswa->id);
                $siswaPenilaian = $penilaianMap->get($siswa->id, collect());
                $siswa->total_nilai_terisi = $siswaPenilaian->whereNotNull('nilai_akhir')->count();
                $siswa->total_capaian_terisi = $siswaPenilaian->filter(fn($p) => !empty($p->capaian_kompetensi))->count();
                $siswa->rata_rata_akhir = $siswaPenilaian->avg('nilai_akhir') ? round($siswaPenilaian->avg('nilai_akhir'), 1) : null;
            }
        }

        return view('pages.rapor.index', [
            'title' => 'Cetak Laporan Hasil Belajar (Rapor Siswa)',
            'school' => $school,
            'currentSemester' => $currentSemester,
            'currentTahunAjaran' => $currentTahunAjaran,
            'kelasList' => $kelasList,
            'selectedKelas' => $selectedKelas,
            'siswas' => $siswas,
            'summary' => $summary,
            'isWalas' => $isWalas,
            'isAdminOrWaka' => $isAdminOrWaka,
        ]);
    }

    /**
     * Tampilan Cetak Rapor (Presisi 100% Mengikuti Standar contoh rapor.pdf)
     */
    public function print(Request $request)
    {
        $user = Auth::user();
        $isAdminOrWaka = $user->isAdmin() || $user->isWaka();
        $isWalas = $user->isWaliKelas();

        if (!$isAdminOrWaka && !$isWalas) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak dokumen rapor.');
        }

        $school = School::first();
        if (!$school) {
            return redirect()->route('dashboard')->with('error', 'Data identitas sekolah belum disinkronkan.');
        }

        $kelasId = $request->query('kelas_id');
        $siswaId = $request->query('siswa_id');

        if (!$kelasId && !$siswaId) {
            return redirect()->route('rapor.index')->with('error', 'Silakan pilih kelas atau peserta didik yang ingin dicetak.');
        }

        if ($siswaId) {
            $siswa = Siswa::with('kelas.waliKelas')->findOrFail($siswaId);
            $kelas = $siswa->kelas;
            $siswas = collect([$siswa]);
        } else {
            $kelas = Kelas::with('waliKelas')->findOrFail($kelasId);
            $siswas = Siswa::where('kelas_id', $kelas->id)->orderBy('nama')->get();
        }

        // Keamanan: Jika Walas, pastikan kelas yang dicetak adalah kelas binaannya
        if ($isWalas && !$isAdminOrWaka) {
            $waliGuru = $user->guru;
            $waliKelasIds = $waliGuru ? $waliGuru->kelasWali->pluck('id')->toArray() : [];
            if ($kelas->wali_kelas_id !== $waliGuru?->id && !in_array($kelas->id, $waliKelasIds) && $kelas->wali_kelas_nama !== $waliGuru?->nama) {
                abort(403, 'Anda hanya diperbolehkan mencetak rapor untuk rombel kelas perwalian Anda sendiri.');
            }
        }

        // Ambil wali kelas guru dan signature
        $waliKelas = $kelas->waliKelas;
        if (!$waliKelas && $kelas->wali_kelas_nama) {
            $waliKelas = Guru::where('nama', $kelas->wali_kelas_nama)->first();
        }

        // Ambil seluruh mapel yang terdaftar di kelas ini
        $mapelIds = $kelas->guruMapelKelas()->pluck('mapel_id')->unique();
        $mapels = Mapel::whereIn('id', $mapelIds)->get();

        // Jika belum ada mapping guruMapelKelas, fallback ambil mapel dari tabel penilaian atau semua mapel sekolah
        if ($mapels->isEmpty()) {
            $mapelsInPenilaian = Nilai::whereIn('siswa_id', $siswas->pluck('id'))->pluck('mapel_id')->unique();
            if ($mapelsInPenilaian->isNotEmpty()) {
                $mapels = Mapel::whereIn('id', $mapelsInPenilaian)->get();
            } else {
                $mapels = Mapel::where('school_id', $school->id)->get();
            }
        }

        // Kelompokkan mapel sesuai standar dokumen rapor:
        // 1. Mata Pelajaran Umum
        // 2. C2. Dasar Program Keahlian / Kejuruan
        // 3. Muatan Lokal
        $groupedMapels = [
            'umum' => collect(),
            'kejuruan' => collect(),
            'mulok' => collect(),
        ];

        foreach ($mapels as $m) {
            $nama = strtolower($m->nama_mapel);
            if (str_contains($nama, 'lampung') || str_contains($nama, 'daerah') || str_contains($nama, 'muatan lokal')) {
                $groupedMapels['mulok']->push($m);
            } elseif (
                str_contains($nama, 'otomotif') ||
                str_contains($nama, 'busana') ||
                str_contains($nama, 'mesin') ||
                str_contains($nama, 'chasis') ||
                str_contains($nama, 'kelistrikan') ||
                str_contains($nama, 'menjahit') ||
                str_contains($nama, 'pola') ||
                str_contains($nama, 'tekstil') ||
                str_contains($nama, 'desain') ||
                str_contains($nama, 'kejuruan') ||
                str_contains($nama, 'produk kreatif') ||
                str_contains($nama, 'bengkel') ||
                str_contains($nama, 'injeksi')
            ) {
                $groupedMapels['kejuruan']->push($m);
            } else {
                $groupedMapels['umum']->push($m);
            }
        }

        // Urutkan mapel umum sesuai standar urutan Kurikulum Merdeka resmi
        $orderMap = [
            'agama' => 1,
            'pancasila' => 2,
            'bahasa indonesia' => 3,
            'matematika' => 4,
            'bahasa inggris' => 5,
            'sejarah' => 6,
            'jasmani' => 7,
            'pjok' => 7,
            'olahraga' => 7,
            'seni' => 8,
            'informatika' => 9,
            'ipas' => 10,
            'ilmu pengetahuan alam dan sosial' => 10,
            'anti korupsi' => 11,
        ];

        $getPriority = function($m) use ($orderMap) {
            $nama = strtolower($m->nama_mapel);
            foreach ($orderMap as $key => $prio) {
                if (str_contains($nama, $key)) return $prio;
            }
            return 99;
        };

        $groupedMapels['umum'] = $groupedMapels['umum']->sortBy($getPriority)->values();
        $groupedMapels['kejuruan'] = $groupedMapels['kejuruan']->sortBy('nama_mapel')->values();
        $groupedMapels['mulok'] = $groupedMapels['mulok']->sortBy('nama_mapel')->values();

        $selectedTahunAjaran = $request->query('tahun_ajaran', $school->tahun_ajaran ?? '2025/2026');
        $selectedSemester = $request->query('semester', $school->semester ?? '2');
        $semesterVariants = ($selectedSemester == '2' || strtolower($selectedSemester) === 'genap') ? ['2', 'Genap'] : ['1', 'Ganjil'];

        // Load Penilaian & Absensi untuk seluruh siswa yang dicetak sesuai periode akademik yang dipilih
        $penilaians = Nilai::whereIn('siswa_id', $siswas->pluck('id'))
            ->where('tahun_ajaran', $selectedTahunAjaran)
            ->whereIn('semester', $semesterVariants)
            ->get()
            ->groupBy('siswa_id');

        $absensis = AbsensiSiswa::whereIn('siswa_id', $siswas->pluck('id'))
            ->where('tahun_ajaran', $selectedTahunAjaran)
            ->whereIn('semester', $semesterVariants)
            ->get()
            ->keyBy('siswa_id');

        return view('pages.rapor.print', [
            'school' => $school,
            'kelas' => $kelas,
            'waliKelas' => $waliKelas,
            'siswas' => $siswas,
            'groupedMapels' => $groupedMapels,
            'penilaians' => $penilaians,
            'absensis' => $absensis,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'selectedSemester' => $selectedSemester,
            'hideSignature' => $request->boolean('hide_signature', false),
        ]);
    }

    /**
     * Update Catatan Wali Kelas, Kokurikuler (P5), & Keterangan Kenaikan Kelas Siswa
     */
    public function updateCatatan(Request $request, $siswaId)
    {
        $siswa = Siswa::with('kelas')->findOrFail($siswaId);
        $user = Auth::user();
        $isAdminOrWaka = $user->isAdmin() || $user->isWaka();
        $isWalas = $user->isWaliKelas();

        if (!$isAdminOrWaka && !$isWalas) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'catatan_wali_kelas' => 'nullable|string|max:1000',
            'catatan_kokurikuler' => 'nullable|string|max:2000',
            'kenaikan_kelas' => 'nullable|string|max:200',
        ]);

        $school = School::first();
        $semester = $request->input('semester', $school->semester ?? '2');
        $tahunAjaran = $request->input('tahun_ajaran', $school->tahun_ajaran ?? '2025/2026');

        $absensi = AbsensiSiswa::firstOrNew([
            'school_id' => $school->id,
            'siswa_id' => $siswa->id,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
        ]);

        $absensi->kelas_id = $siswa->kelas_id;
        $absensi->catatan_wali_kelas = $validated['catatan_wali_kelas'];
        $absensi->catatan_kokurikuler = $validated['catatan_kokurikuler'];
        $absensi->kenaikan_kelas = $validated['kenaikan_kelas'];
        if (!$absensi->exists) {
            $absensi->masuk = 0;
            $absensi->sakit = 0;
            $absensi->izin = 0;
            $absensi->alpha = 0;
            $absensi->bolos = 0;
        }
        $absensi->save();

        return back()->with('success', "Catatan rapor dan kokurikuler untuk {$siswa->nama} berhasil disimpan.");
    }

    /**
     * Terapkan Catatan Kokurikuler (P5) Secara Massal ke Seluruh Siswa Rombel
     */
    public function updateKokurikulerMassal(Request $request, $kelasId)
    {
        $kelas = Kelas::findOrFail($kelasId);
        $user = Auth::user();
        $isAdminOrWaka = $user->isAdmin() || $user->isWaka();
        $isWalas = $user->isWaliKelas();

        if (!$isAdminOrWaka && !$isWalas) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'narasi_kokurikuler' => 'required|string|max:3000',
        ]);

        $school = School::first();
        $siswas = Siswa::where('kelas_id', $kelas->id)->get();
        $semester = $request->input('semester', $school->semester ?? '2');
        $tahunAjaran = $request->input('tahun_ajaran', $school->tahun_ajaran ?? '2025/2026');

        foreach ($siswas as $siswa) {
            $absensi = AbsensiSiswa::firstOrNew([
                'school_id' => $school->id,
                'siswa_id' => $siswa->id,
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
            ]);

            $absensi->kelas_id = $kelas->id;
            $absensi->catatan_kokurikuler = $validated['narasi_kokurikuler'];
            if (!$absensi->exists) {
                $absensi->masuk = 0;
                $absensi->sakit = 0;
                $absensi->izin = 0;
                $absensi->alpha = 0;
                $absensi->bolos = 0;
            }
            $absensi->save();
        }

        return back()->with('success', "Catatan projek kokurikuler (P5) berhasil diterapkan ke seluruh {$siswas->count()} peserta didik kelas {$kelas->nama_kelas}.");
    }
}
