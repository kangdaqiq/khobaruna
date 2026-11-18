<?php

namespace App\Console\Commands;

use App\Services\DataSyncService;
use Illuminate\Console\Command;

class SyncDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-data {mode=auto : Sync mode (auto, db, or api)} {--school_id= : ID Sekolah spesifik yang disinkronkan} {--all : Nonaktifkan reset single school}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data guru, siswa, kelas, mapel dan akun user dari backend web absensi dibatasi 1 sekolah';

    /**
     * Execute the console command.
     */
    public function handle(DataSyncService $syncService): int
    {
        $mode = $this->argument('mode');
        $schoolId = $this->option('school_id') ? (int) $this->option('school_id') : (int) env('SYNC_SCHOOL_ID', 4);
        $resetFirst = !$this->option('all');

        $this->info("Memulai sinkronisasi data untuk School ID: [{$schoolId}] dengan mode [{$mode}]...");

        $result = $syncService->sync($schoolId, $mode, $resetFirst);

        if ($result['success']) {
            $this->info("✓ " . $result['message']);
            if (isset($result['stats'])) {
                $this->table(['Entitas', 'Jumlah'], [
                    ['Nama Sekolah', $result['school_name'] ?? '-'],
                    ['Guru', $result['stats']['gurus_count']],
                    ['Siswa', $result['stats']['siswas_count']],
                    ['Kelas', $result['stats']['kelases_count']],
                    ['Mapel', $result['stats']['mapels_count']],
                    ['Akun Pengguna Baru', $result['stats']['users_count']],
                ]);
            }
            return Command::SUCCESS;
        }

        $this->error("✗ " . $result['message']);
        return Command::FAILURE;
    }
}
