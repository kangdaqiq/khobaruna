<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\School;
use App\Models\Siswa;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\DataSyncService;
use App\Services\ExamSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Tampilkan Halaman Pusat Sinkronisasi
     */
    public function index(DataSyncService $syncService, ExamSyncService $examSyncService)
    {
        // 1. Data Lokal E-Rapor
        $currentSchool = School::first();
        $localCounts = [
            'gurus' => Guru::count(),
            'siswas' => Siswa::count(),
            'kelases' => Kelas::count(),
            'mapels' => Mapel::count(),
            'users' => User::count(),
        ];

        // 2. Daftar Sekolah Sumber & Cek Koneksi DB Absen
        $sourceDbStatus = false;
        $sourceSchools = [];
        $sourceCounts = [
            'gurus' => 0,
            'siswas' => 0,
            'kelases' => 0,
            'mapels' => 0,
        ];

        try {
            $db = DB::connection('source_db');
            $db->getPdo();
            $sourceDbStatus = true;
            $sourceSchools = $syncService->getSourceSchools();

            $activeSchoolId = $currentSchool?->sync_id ?: (int) env('SYNC_SCHOOL_ID', 4);

            $sourceCounts['gurus'] = $db->table('guru')->where('school_id', $activeSchoolId)->count();
            $sourceCounts['siswas'] = $db->table('siswa')->where('school_id', $activeSchoolId)->count();
            $sourceCounts['kelases'] = $db->table('kelas')->where('school_id', $activeSchoolId)->count();
            $sourceCounts['mapels'] = $db->table('mapel')->where('school_id', $activeSchoolId)->count();
        } catch (\Throwable $e) {
            $sourceDbStatus = false;
        }

        // 3. Status Koneksi & Data Exam Web (CBT)
        $examSummary = $examSyncService->getSasSummary($currentSchool?->id);

        // 4. Riwayat Log Sinkronisasi
        $logs = SyncLog::latest()->take(10)->get();

        return view('pages.admin.sync.index', [
            'title' => 'Pusat Sinkronisasi Data - E-Rapor',
            'currentSchool' => $currentSchool,
            'localCounts' => $localCounts,
            'sourceCounts' => $sourceCounts,
            'sourceSchools' => $sourceSchools,
            'sourceDbStatus' => $sourceDbStatus,
            'examSummary' => $examSummary,
            'logs' => $logs,
        ]);
    }

    /**
     * Jalankan proses sinkronisasi data dibatasi 1 sekolah dari Web Absen
     */
    public function run(Request $request, DataSyncService $syncService)
    {
        $schoolId = $request->input('school_id') ? (int) $request->input('school_id') : null;
        $mode = $request->input('mode', 'auto');

        $result = $syncService->sync($schoolId, $mode, true);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Jalankan proses sinkronisasi nilai SAS massal dari Exam Web
     */
    public function runExamSync(Request $request, ExamSyncService $examSyncService)
    {
        $schoolId = $request->input('school_id') ? (int) $request->input('school_id') : null;

        $result = $examSyncService->syncAllSasScores($schoolId);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
