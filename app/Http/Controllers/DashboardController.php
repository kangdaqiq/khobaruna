<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruMapelKelas;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\School;
use App\Models\Siswa;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dispatcher utama: redirect otomatis berdasarkan role user aktif
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            // Auto login admin seeder jika belum login saat dev
            $defaultUser = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
                $user = $defaultUser;
            } else {
                return redirect()->route('login');
            }
        }

        return match ($user->role) {
            User::ROLE_ADMIN => redirect()->route('dashboard.admin'),
            User::ROLE_WAKA_KURIKULUM => redirect()->route('dashboard.waka-kurikulum'),
            User::ROLE_WALI_KELAS => redirect()->route('dashboard.wali-kelas'),
            User::ROLE_GURU => redirect()->route('dashboard.guru'),
            User::ROLE_SISWA => redirect()->route('dashboard.siswa'),
            default => redirect()->route('dashboard.admin'),
        };
    }

    /**
     * 1. Dashboard Super Admin
     */
    public function admin()
    {
        $school = School::first();
        $counts = [
            'gurus' => Guru::count(),
            'siswas' => Siswa::count(),
            'kelases' => Kelas::count(),
            'mapels' => Mapel::count(),
            'users' => User::count(),
        ];

        $recentSync = SyncLog::latest()->take(5)->get();
        $recentUsers = User::latest()->take(6)->get();

        return view('pages.dashboard.admin', [
            'title' => 'Dashboard Super Admin - E-Rapor',
            'school' => $school,
            'counts' => $counts,
            'recentSync' => $recentSync,
            'recentUsers' => $recentUsers,
        ]);
    }

    /**
     * 2. Dashboard Waka Kurikulum
     */
    public function wakaKurikulum()
    {
        $counts = [
            'gurus' => Guru::count(),
            'siswas' => Siswa::count(),
            'kelases' => Kelas::count(),
            'mapels' => Mapel::count(),
        ];

        $kelases = Kelas::withCount('siswas')->take(10)->get();
        $mapels = Mapel::take(8)->get();

        return view('pages.dashboard.waka-kurikulum', [
            'title' => 'Dashboard Waka Kurikulum - E-Rapor',
            'counts' => $counts,
            'kelases' => $kelases,
            'mapels' => $mapels,
        ]);
    }

    /**
     * 3. Dashboard Wali Kelas
     */
    public function waliKelas()
    {
        $user = Auth::user();
        $guru = $user?->guru ?? Guru::where('is_wali_kelas', true)->first();
        $kelas = $guru?->kelasWali ?? Kelas::first();
        $siswas = $kelas ? Siswa::where('kelas_id', $kelas->id)->take(15)->get() : collect([]);

        return view('pages.dashboard.wali-kelas', [
            'title' => 'Dashboard Wali Kelas - E-Rapor',
            'guru' => $guru,
            'kelas' => $kelas,
            'siswas' => $siswas,
        ]);
    }

    /**
     * 4. Dashboard Guru Mata Pelajaran
     */
    public function guru()
    {
        $user = Auth::user();
        $guru = $user?->guru ?? Guru::first();

        // Ambil penugasan mengajar riil guru yang login
        $assignments = $guru
            ? GuruMapelKelas::with(['kelas.siswas', 'mapel'])->where('guru_id', $guru->id)->get()
            : collect();

        $kelasesCount = $assignments->pluck('kelas_id')->unique()->count();
        $mapelsCount = $assignments->pluck('mapel_id')->unique()->count();
        $totalSiswa = $assignments->sum(fn($a) => $a->kelas?->siswas->count() ?? 0);

        return view('pages.dashboard.guru', [
            'title' => 'Dashboard Guru Mapel - E-Rapor',
            'guru' => $guru,
            'assignments' => $assignments,
            'kelasesCount' => $kelasesCount,
            'mapelsCount' => $mapelsCount,
            'totalSiswa' => $totalSiswa,
        ]);
    }

    /**
     * 5. Dashboard Siswa
     */
    public function siswa()
    {
        $user = Auth::user();
        $siswa = $user?->siswa ?? Siswa::with('kelas')->first();

        return view('pages.dashboard.siswa', [
            'title' => 'Dashboard Siswa - E-Rapor',
            'siswa' => $siswa,
        ]);
    }
}
