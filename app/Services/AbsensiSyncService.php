<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\School;
use App\Models\Siswa;
use App\Models\SyncLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbsensiSyncService
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $rawUrl = config('services.sync.api_url') ?: env('SYNC_API_URL', 'http://absen.jagattech.my.id/api/cbt-sync');
        $this->apiUrl = rtrim($rawUrl, '/');
        $this->apiKey = config('services.sync.api_key') ?: env('SYNC_API_KEY', 'cbt_secret_sync_2026');
    }

    /**
     * Cek status koneksi HTTP API ke Backend Web Absen
     */
    public function checkApiConnection(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Exam-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->apiUrl}/ping");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'connected' => true,
                    'server_name' => $data['server_name'] ?? 'Web Absensi Backend',
                    'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
                    'api_url' => $this->apiUrl,
                    'message' => 'REST API Web Absen online dan terhubung.',
                ];
            }

            return [
                'connected' => false,
                'error' => "HTTP Status {$response->status()}: " . $response->body(),
                'api_url' => $this->apiUrl,
                'message' => 'Gagal terhubung ke API Web Absen.',
            ];
        } catch (\Throwable $e) {
            Log::warning("Gagal ping backend absensi: {$e->getMessage()}");
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'message' => 'Backend Web Absen tidak dapat dihubungi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sinkronkan rekapitulasi absensi (total masuk, sakit, izin, alpha, bolos)
     * Menggunakan REST API Backend Web Absen (Port 8080)
     */
    public function syncRekap(array $params): array
    {
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $tahunAjaran = $params['tahun_ajaran'] ?? '2024/2025';
        $semester = $params['semester'] ?? 'Ganjil';
        $filterKelasId = !empty($params['kelas_id']) ? (int) $params['kelas_id'] : null;

        if (!$startDate || !$endDate) {
            throw new Exception("Rentang tanggal mulai dan tanggal selesai semester wajib ditentukan.");
        }

        $startDateFormatted = Carbon::parse($startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::parse($endDate)->format('Y-m-d');

        if ($startDateFormatted > $endDateFormatted) {
            throw new Exception("Tanggal mulai semester tidak boleh lebih besar dari tanggal selesai.");
        }

        $localSchool = School::first();
        if (!$localSchool) {
            throw new Exception("Data sekolah lokal belum terdaftar. Silakan lakukan sinkronisasi data master terlebih dahulu.");
        }

        // Tentukan school_id sumber di Web Absensi (dari sync_id atau default ENV)
        $sourceSchoolId = $localSchool->sync_id ?: (int) env('SYNC_SCHOOL_ID', 6);

        // Jika ada filter kelas lokal, cari sync_id kelas sumber tersebut
        $sourceKelasId = null;
        if ($filterKelasId) {
            $localKelas = Kelas::where('school_id', $localSchool->id)->find($filterKelasId);
            $sourceKelasId = $localKelas?->sync_id;
        }

        $log = SyncLog::create([
            'source_url' => "http_api (rekap-absensi) [Rentang: {$startDateFormatted} s/d {$endDateFormatted}]",
            'status' => 'in_progress',
            'message' => "Memulai penarikan rekap absensi via backend web absen untuk sekolah {$localSchool->name}...",
        ]);

        try {
            $queryParams = [
                'school_id' => $sourceSchoolId,
                'start_date' => $startDateFormatted,
                'end_date' => $endDateFormatted,
            ];

            if ($sourceKelasId) {
                $queryParams['kelas_id'] = $sourceKelasId;
            }

            $response = Http::timeout(60)
                ->withHeaders([
                    'X-Exam-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->apiUrl}/rekap-absensi", $queryParams);

            if (!$response->successful()) {
                throw new Exception("Backend API mengembalikan kode HTTP {$response->status()}: " . $response->body());
            }

            $body = $response->json();
            if (empty($body['success'])) {
                throw new Exception($body['message'] ?? 'Respon backend tidak valid.');
            }

            $rekapList = $body['rekap'] ?? [];
            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($rekapList as $item) {
                $syncId = $item['siswa_sync_id'] ?? null;
                $nis = $item['nis'] ?? null;

                // Cari siswa di database lokal berdasarkan sync_id atau NIS
                $siswa = Siswa::where('school_id', $localSchool->id)
                    ->where(function ($q) use ($syncId, $nis) {
                        if ($syncId) {
                            $q->where('sync_id', $syncId);
                        }
                        if ($nis) {
                            $q->orWhere('nis', $nis);
                        }
                    })
                    ->first();

                if (!$siswa) {
                    $skippedCount++;
                    continue;
                }

                $masuk = (int) ($item['masuk'] ?? 0);
                $hadir = (int) ($item['hadir'] ?? 0);
                $terlambat = (int) ($item['terlambat'] ?? 0);
                $izin = (int) ($item['izin'] ?? 0);
                $sakit = (int) ($item['sakit'] ?? 0);
                $alpha = (int) ($item['alpha'] ?? 0);
                $bolos = (int) ($item['bolos'] ?? 0);
                $totalHari = (int) ($item['total_hari'] ?? ($masuk + $izin + $sakit + $alpha + $bolos));

                $persentase = $totalHari > 0 ? round(($masuk / $totalHari) * 100, 2) : 0.00;

                AbsensiSiswa::updateOrCreate(
                    [
                        'school_id' => $localSchool->id,
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester' => $semester,
                    ],
                    [
                        'kelas_id' => $siswa->kelas_id,
                        'start_date' => $startDateFormatted,
                        'end_date' => $endDateFormatted,
                        'masuk' => $masuk,
                        'hadir' => $hadir,
                        'terlambat' => $terlambat,
                        'izin' => $izin,
                        'sakit' => $sakit,
                        'alpha' => $alpha,
                        'bolos' => $bolos,
                        'total_hari' => $totalHari,
                        'persentase' => $persentase,
                    ]
                );

                $syncedCount++;
            }

            $successMsg = "Berhasil menyinkronkan rekap absensi untuk {$syncedCount} siswa dari backend web absen. Rentang: {$startDateFormatted} s/d {$endDateFormatted} (T.A {$tahunAjaran} Semester {$semester}).";

            $log->update([
                'status' => 'success',
                'message' => $successMsg,
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'skipped_count' => $skippedCount,
                'total_from_api' => count($rekapList),
                'start_date' => $startDateFormatted,
                'end_date' => $endDateFormatted,
                'message' => $successMsg,
            ];
        } catch (\Throwable $th) {
            Log::error("AbsensiSyncService error: {$th->getMessage()}");
            $log->update([
                'status' => 'failed',
                'message' => "Gagal: {$th->getMessage()}",
            ]);

            return [
                'success' => false,
                'message' => "Gagal sinkronisasi rekap absensi: {$th->getMessage()}",
            ];
        }
    }
}

