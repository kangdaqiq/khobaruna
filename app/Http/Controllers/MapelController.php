<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\School;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index(Request $request)
    {
        $school = School::first();
        $query = Mapel::withCount(['guruMapelKelas as total_kelas']);

        if ($school) {
            $query->where('school_id', $school->id);
        }

        if ($request->filled('kelompok')) {
            $query->where('kelompok', $request->kelompok);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_mapel', 'like', "%{$s}%")
                    ->orWhere('kode_mapel', 'like', "%{$s}%");
            });
        }

        $mapels = $query->orderBy('kelompok')->orderBy('nama_mapel')->paginate(20)->withQueryString();

        $kelompokCounts = [
            'all' => Mapel::when($school, fn($q) => $q->where('school_id', $school->id))->count(),
            'Umum' => Mapel::when($school, fn($q) => $q->where('school_id', $school->id))->where('kelompok', 'Umum')->count(),
            'Kejuruan' => Mapel::when($school, fn($q) => $q->where('school_id', $school->id))->where('kelompok', 'Kejuruan')->count(),
            'Muatan Lokal' => Mapel::when($school, fn($q) => $q->where('school_id', $school->id))->where('kelompok', 'Muatan Lokal')->count(),
        ];

        return view('pages.admin.mapels.index', compact('mapels', 'kelompokCounts', 'school'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:255',
            'kode_mapel' => 'nullable|string|max:50',
            'kelompok' => 'required|in:Umum,Kejuruan,Muatan Lokal,Bimbingan',
        ]);

        $school = School::first();
        Mapel::create([
            'school_id' => $school?->id,
            'nama_mapel' => $validated['nama_mapel'],
            'kode_mapel' => $validated['kode_mapel'] ?? strtoupper(substr($validated['nama_mapel'], 0, 4)),
            'kelompok' => $validated['kelompok'],
        ]);

        return redirect()->route('admin.mapels.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Mapel $mapel)
    {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:255',
            'kode_mapel' => 'nullable|string|max:50',
            'kelompok' => 'required|in:Umum,Kejuruan,Muatan Lokal,Bimbingan',
        ]);

        $mapel->update($validated);

        return redirect()->route('admin.mapels.index')->with('success', 'Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy(Mapel $mapel)
    {
        $mapel->guruMapelKelas()->delete();
        $mapel->delete();

        return redirect()->route('admin.mapels.index')->with('success', 'Mata Pelajaran berhasil dihapus.');
    }
}

