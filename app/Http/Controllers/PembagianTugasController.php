<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruMapelKelas;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\School;
use Illuminate\Http\Request;

class PembagianTugasController extends Controller
{
    public function index(Request $request)
    {
        $school = School::first();
        $query = GuruMapelKelas::with(['guru', 'mapel', 'kelas.siswas']);

        if ($school) {
            $query->where('school_id', $school->id);
        }

        if ($request->filled('guru_id')) {
            $query->where('guru_id', $request->guru_id);
        }
        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $assignments = $query->orderBy('guru_id')->orderBy('kelas_id')->paginate(25)->withQueryString();

        $gurus = Guru::when($school, fn($q) => $q->where('school_id', $school->id))->orderBy('nama')->get();
        $mapels = Mapel::when($school, fn($q) => $q->where('school_id', $school->id))->orderBy('nama_mapel')->get();
        $kelases = Kelas::when($school, fn($q) => $q->where('school_id', $school->id))->orderBy('nama_kelas')->get();

        $stats = [
            'total_assignments' => GuruMapelKelas::when($school, fn($q) => $q->where('school_id', $school->id))->count(),
            'total_gurus_active' => GuruMapelKelas::when($school, fn($q) => $q->where('school_id', $school->id))->distinct('guru_id')->count('guru_id'),
            'total_kelases_covered' => GuruMapelKelas::when($school, fn($q) => $q->where('school_id', $school->id))->distinct('kelas_id')->count('kelas_id'),
        ];

        return view('pages.admin.pembagian-tugas.index', compact('assignments', 'gurus', 'mapels', 'kelases', 'stats', 'school'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guru_id' => 'required|exists:gurus,id',
            'mapel_id' => 'required|exists:mapels,id',
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'exists:kelases,id',
            'tahun_ajaran' => 'nullable|string',
            'semester' => 'nullable|string',
        ]);

        $school = School::first();
        $tahun = $validated['tahun_ajaran'] ?? '2024/2025';
        $semester = $validated['semester'] ?? 'Ganjil';

        $createdCount = 0;
        foreach ($validated['kelas_ids'] as $kId) {
            GuruMapelKelas::updateOrCreate(
                [
                    'guru_id' => $validated['guru_id'],
                    'mapel_id' => $validated['mapel_id'],
                    'kelas_id' => $kId,
                    'tahun_ajaran' => $tahun,
                    'semester' => $semester,
                ],
                [
                    'school_id' => $school?->id,
                ]
            );
            $createdCount++;
        }

        return redirect()->route('admin.pembagian-tugas.index')
            ->with('success', "Berhasil menambahkan pembagian tugas mengajar untuk {$createdCount} kelas.");
    }

    public function destroy($id)
    {
        $assignment = GuruMapelKelas::findOrFail($id);
        $assignment->delete();

        return redirect()->route('admin.pembagian-tugas.index')
            ->with('success', 'Pembagian tugas mengajar berhasil dihapus.');
    }
}

