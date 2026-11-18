<?php

namespace App\Http\Controllers;

use App\Models\CapaianPembelajaran;
use App\Models\GuruMapelKelas;
use App\Models\Mapel;
use App\Models\School;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CpTpController extends Controller
{
    /**
     * Tampilkan Halaman Pengelolaan CP & TP
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $guru = $user?->guru;
        $school = School::first();

        // Cek apakah user memiliki penugasan mengajar di guru_mapel_kelas
        $hasAssignments = $guru ? GuruMapelKelas::where('guru_id', $guru->id)->exists() : false;

        // Mode supervisi kurikulum (khusus Admin / Waka yang secara sengaja ingin melihat seluruh mapel sekolah)
        $isSupervisi = $request->boolean('supervisi', false);

        // Jika user memiliki data profil guru dan penugasan mengajar, BATASI HANYA MAPEL & KELAS YANG DIAMPU!
        $filterByGuru = $guru && $hasAssignments && !$isSupervisi;

        if ($filterByGuru) {
            // 1. Ambil HANYA mapel yang tercatat di SK pembagian tugas mengajar guru ini
            $assignedMapelIds = GuruMapelKelas::where('guru_id', $guru->id)->pluck('mapel_id')->unique();
            $mapels = Mapel::whereIn('id', $assignedMapelIds)->orderBy('nama_mapel')->get();

            // Tentukan mapel aktif (pastikan valid di antara mapel yang diampu)
            $selectedMapelId = $request->input('mapel_id');
            if (!$selectedMapelId || !$assignedMapelIds->contains($selectedMapelId)) {
                $selectedMapelId = $mapels->first()?->id;
            }

            // 2. Ambil HANYA kelas dan tingkat yang diajar untuk mapel yang sedang dipilih
            $assignedClassRecords = GuruMapelKelas::with('kelas')
                ->where('guru_id', $guru->id)
                ->where('mapel_id', $selectedMapelId)
                ->get()
                ->pluck('kelas')
                ->filter();

            // Kelompokkan tingkat kelas yang benar-benar diajar guru ini
            $availableTingkats = $assignedClassRecords->groupBy('tingkat')->map(function ($classes, $tingkat) {
                $classNames = $classes->pluck('nama_kelas')->unique()->implode(', ');
                $fase = match ((string) $tingkat) {
                    '11', '12' => 'F',
                    default => 'E',
                };
                return [
                    'tingkat' => (string) $tingkat,
                    'fase' => $fase,
                    'label' => "Kelas {$tingkat} (Fase {$fase}) — {$classNames}",
                    'classes' => $classNames,
                ];
            })->sortBy('tingkat')->values();

            // Pastikan selectedTingkat valid di antara tingkat yang diampu
            $validTingkats = $availableTingkats->pluck('tingkat')->toArray();
            $selectedTingkat = (string) $request->input('tingkat');
            if (!$selectedTingkat || !in_array($selectedTingkat, $validTingkats)) {
                $selectedTingkat = $validTingkats[0] ?? '10';
            }
        } else {
            // Admin atau Waka Kurikulum mode supervisi seluruh sekolah
            $mapels = Mapel::orderBy('nama_mapel')->get();
            $selectedMapelId = $request->input('mapel_id', $mapels->first()?->id);

            $availableTingkats = collect([
                ['tingkat' => '10', 'fase' => 'E', 'label' => 'Kelas 10 (Fase E) — Seluruh Rombel', 'classes' => 'Semua Rombel'],
                ['tingkat' => '11', 'fase' => 'F', 'label' => 'Kelas 11 (Fase F) — Seluruh Rombel', 'classes' => 'Semua Rombel'],
                ['tingkat' => '12', 'fase' => 'F', 'label' => 'Kelas 12 (Fase F) — Seluruh Rombel', 'classes' => 'Semua Rombel'],
            ]);

            $selectedTingkat = (string) $request->input('tingkat', '10');
        }

        $selectedFase = match ($selectedTingkat) {
            '11', '12' => 'F',
            default => 'E',
        };
        $activeTab = $request->input('tab', 'cp'); // CP di awal, baru TP

        // Query CP ketat per tingkatan kelas (10, 11, atau 12)
        $cpQuery = CapaianPembelajaran::with(['mapel', 'tujuanPembelajarans'])
            ->where('tingkat', $selectedTingkat);

        if ($selectedMapelId) {
            $cpQuery->where('mapel_id', $selectedMapelId);
        }
        $cps = $cpQuery->latest()->get();

        // Query TP ketat per tingkatan kelas (10, 11, atau 12)
        $tpQuery = TujuanPembelajaran::with(['mapel', 'cp', 'guru'])
            ->where('tingkat', $selectedTingkat);

        if ($selectedMapelId) {
            $tpQuery->where('mapel_id', $selectedMapelId);
        }
        $tps = $tpQuery->orderBy('urutan')->get();

        $selectedMapel = Mapel::find($selectedMapelId);

        return view('pages.kurikulum.cp-tp.index', compact(
            'mapels',
            'cps',
            'tps',
            'selectedMapelId',
            'selectedTingkat',
            'selectedFase',
            'selectedMapel',
            'availableTingkats',
            'activeTab',
            'filterByGuru',
            'hasAssignments',
            'isSupervisi',
            'guru'
        ));
    }

    /**
     * Simpan Capaian Pembelajaran (CP) Baru - Spesifik per Tingkat Kelas (10, 11, atau 12)
     */
    public function storeCp(Request $request)
    {
        $request->validate([
            'mapel_id' => 'required|exists:mapels,id',
            'tingkat' => 'required|in:10,11,12',
            'elemen' => 'required|string|max:255',
            'deskripsi' => 'required|string',
        ]);

        $tingkat = (string) $request->tingkat;
        $fase = match ($tingkat) {
            '11', '12' => 'F',
            default => 'E',
        };

        $school = School::first();

        CapaianPembelajaran::create([
            'school_id' => $school?->id,
            'mapel_id' => $request->mapel_id,
            'fase' => $fase,
            'tingkat' => $tingkat,
            'elemen' => $request->elemen,
            'deskripsi' => $request->deskripsi,
            'is_active' => true,
        ]);

        return redirect()->route('kurikulum.cp-tp.index', [
            'mapel_id' => $request->mapel_id,
            'tingkat' => $tingkat,
            'tab' => 'cp'
        ])->with('success', "Capaian Pembelajaran (CP) untuk Kelas {$tingkat} berhasil ditambahkan.");
    }

    /**
     * Update Capaian Pembelajaran (CP)
     */
    public function updateCp(Request $request, $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);

        $request->validate([
            'tingkat' => 'required|in:10,11,12',
            'elemen' => 'required|string|max:255',
            'deskripsi' => 'required|string',
        ]);

        $tingkat = (string) $request->tingkat;
        $fase = match ($tingkat) {
            '11', '12' => 'F',
            default => 'E',
        };

        $cp->update([
            'elemen' => $request->elemen,
            'deskripsi' => $request->deskripsi,
            'fase' => $fase,
            'tingkat' => $tingkat,
        ]);

        return redirect()->route('kurikulum.cp-tp.index', [
            'mapel_id' => $cp->mapel_id,
            'tingkat' => $tingkat,
            'tab' => 'cp'
        ])->with('success', "Capaian Pembelajaran (CP) Kelas {$tingkat} berhasil diperbarui.");
    }

    /**
     * Hapus Capaian Pembelajaran (CP)
     */
    public function destroyCp($id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        $mapelId = $cp->mapel_id;
        $tingkat = $cp->tingkat ?: '10';
        $cp->delete();

        return redirect()->route('kurikulum.cp-tp.index', [
            'mapel_id' => $mapelId,
            'tingkat' => $tingkat,
            'tab' => 'cp'
        ])->with('success', 'Capaian Pembelajaran (CP) berhasil dihapus.');
    }

    /**
     * Simpan Tujuan Pembelajaran (TP) Baru
     * Catatan: CP bersifat opsional (bisa langsung input TP)
     */
    public function storeTp(Request $request)
    {
        $request->validate([
            'mapel_id' => 'required|exists:mapels,id',
            'cp_id' => 'nullable|exists:capaian_pembelajarans,id',
            'kode_tp' => 'required|string|max:30',
            'deskripsi' => 'required|string',
            'ringkasan_materi' => 'nullable|string|max:255',
            'tingkat' => 'required|in:10,11,12',
            'semester' => 'required|in:Ganjil,Genap',
            'urutan' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $school = School::first();

        // Dapatkan urutan otomatis jika tidak diisi
        $urutan = $request->urutan;
        if (!$urutan) {
            $last = TujuanPembelajaran::where('mapel_id', $request->mapel_id)
                ->where('tingkat', $request->tingkat)
                ->where('semester', $request->semester)
                ->max('urutan');
            $urutan = ($last ?? 0) + 1;
        }

        TujuanPembelajaran::create([
            'school_id' => $school?->id,
            'mapel_id' => $request->mapel_id,
            'cp_id' => $request->cp_id,
            'guru_id' => $user?->guru?->id,
            'tingkat' => $request->tingkat,
            'fase' => in_array($request->tingkat, ['11', '12']) ? 'F' : 'E',
            'semester' => $request->semester,
            'kode_tp' => $request->kode_tp,
            'deskripsi' => $request->deskripsi,
            'ringkasan_materi' => $request->ringkasan_materi,
            'urutan' => $urutan,
            'is_active' => true,
        ]);

        return redirect()->route('kurikulum.cp-tp.index', [
            'mapel_id' => $request->mapel_id,
            'tingkat' => $request->tingkat,
            'tab' => 'tp'
        ])->with('success', "Tujuan Pembelajaran ({$request->kode_tp}) berhasil ditambahkan.");
    }

    /**
     * Update Tujuan Pembelajaran (TP)
     */
    public function updateTp(Request $request, $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);

        $request->validate([
            'cp_id' => 'nullable|exists:capaian_pembelajarans,id',
            'kode_tp' => 'required|string|max:30',
            'deskripsi' => 'required|string',
            'ringkasan_materi' => 'nullable|string|max:255',
            'urutan' => 'nullable|integer',
        ]);

        $tp->update([
            'cp_id' => $request->cp_id,
            'kode_tp' => $request->kode_tp,
            'deskripsi' => $request->deskripsi,
            'ringkasan_materi' => $request->ringkasan_materi,
            'urutan' => $request->urutan ?: $tp->urutan,
        ]);

        return redirect()->route('kurikulum.cp-tp.index', [
            'mapel_id' => $tp->mapel_id,
            'tingkat' => $tp->tingkat,
            'tab' => 'tp'
        ])->with('success', "Tujuan Pembelajaran ({$tp->kode_tp}) berhasil diperbarui.");
    }

    /**
     * Hapus Tujuan Pembelajaran (TP)
     */
    public function destroyTp($id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        $mapelId = $tp->mapel_id;
        $tingkat = $tp->tingkat;
        $tp->delete();

        return redirect()->route('kurikulum.cp-tp.index', [
            'mapel_id' => $mapelId,
            'tingkat' => $tingkat,
            'tab' => 'tp'
        ])->with('success', 'Tujuan Pembelajaran (TP) berhasil dihapus.');
    }
}

