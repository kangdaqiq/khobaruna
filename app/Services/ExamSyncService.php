<?php

namespace App\Services;

use App\Models\GuruMapelKelas;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\School;
use App\Models\Siswa;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamSyncService
{
    /**
     * Cek apakah koneksi ke database exam_web aktif
     */
    public function checkConnection(): bool
    {
        try {
            DB::connection('exam_db')->getPdo();
            return true;
        } catch (\Throwable $e) {
            Log::error("Koneksi exam_db gagal: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dapatkan ringkasan ujian SAS yang siap disinkronkan dari exam_web
     */
    public function getSasSummary(?int $schoolId = null): array
    {
        if (!$this->checkConnection()) {
            return [
                'connected' => false,
                'total_ujian' => 0,
                'total_peserta_selesai' => 0,
                'ujian_list' => [],
            ];
        }

        try {
            $query = DB::connection('exam_db')->table('ujians as u')
                ->leftJoin('mapels as m', 'u.mapel_id', '=', 'm.id')
                ->where(function ($q) {
                    $q->where('u.tipe_ujian', 'asesmen_akhir_semester')
                      ->orWhere('u.exam_id', 'LIKE', 'SAS-%');
                })
                ->select(
                    'u.id',
                    'u.exam_id',
                    'u.nama_ujian',
                    'u.tipe_ujian',
                    'u.status',
                    'u.tanggal',
                    'm.nama_mapel',
                    'm.sync_id as mapel_sync_id'
                );

            $ujians = $query->get();

            $totalPesertaSelesai = 0;
            $ujianList = [];

            foreach ($ujians as $u) {
                $pesertaCount = DB::connection('exam_db')->table('ujian_pesertas')
                    ->where('ujian_id', $u->id)
                    ->where('status', 'SELESAI')
                    ->whereNotNull('nilai')
                    ->count();

                $totalPesertaSelesai += $pesertaCount;

                $ujianList[] = [
                    'id' => $u->id,
                    'exam_id' => $u->exam_id,
                    'nama_ujian' => $u->nama_ujian,
                    'nama_mapel' => $u->nama_mapel,
                    'status' => $u->status,
                    'tanggal' => $u->tanggal,
                    'jumlah_selesai' => $pesertaCount,
                ];
            }

            return [
                'connected' => true,
                'total_ujian' => $ujians->count(),
                'total_peserta_selesai' => $totalPesertaSelesai,
                'ujian_list' => $ujianList,
            ];
        } catch (\Throwable $e) {
            Log::error("Gagal getSasSummary: " . $e->getMessage());
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'total_ujian' => 0,
                'total_peserta_selesai' => 0,
                'ujian_list' => [],
            ];
        }
    }

    /**
     * Dapatkan daftar seluruh pelaksanaan ujian di Exam Web yang relevan untuk mata pelajaran ini
     */
    public function getAvailableExamsForMapel(int $mapelId): array
    {
        if (!$this->checkConnection()) {
            return [];
        }

        $mapel = Mapel::find($mapelId);
        if (!$mapel) return [];

        $examMapel = null;
        if (!empty($mapel->sync_id)) {
            $examMapel = DB::connection('exam_db')->table('mapels')
                ->where('sync_id', $mapel->sync_id)
                ->first();
        }
        if (!$examMapel) {
            $examMapel = DB::connection('exam_db')->table('mapels')
                ->where('nama_mapel', 'LIKE', '%' . trim($mapel->nama_mapel) . '%')
                ->first();
        }

        if (!$examMapel) {
            return [];
        }

        try {
            $ujians = DB::connection('exam_db')->table('ujians')
                ->where('mapel_id', $examMapel->id)
                ->orderBy('id', 'desc')
                ->get();

            $list = [];
            foreach ($ujians as $u) {
                $pesertaCount = DB::connection('exam_db')->table('ujian_pesertas')
                    ->where('ujian_id', $u->id)
                    ->where('status', 'SELESAI')
                    ->whereNotNull('nilai')
                    ->count();

                $list[] = [
                    'id' => $u->id,
                    'exam_id' => $u->exam_id,
                    'nama_ujian' => $u->nama_ujian,
                    'tipe_ujian' => $u->tipe_ujian,
                    'status' => $u->status,
                    'tanggal' => $u->tanggal,
                    'jumlah_peserta_selesai' => $pesertaCount,
                ];
            }

            return $list;
        } catch (\Throwable $e) {
            Log::error("Gagal getAvailableExamsForMapel: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tarik nilai SAS khusus untuk 1 Rombel & 1 Mapel tertentu (digunakan oleh Guru di Lembar Nilai)
     */
    public function syncSasForClassSubject(int $kelasId, int $mapelId, string $tahunAjaran = '2024/2025', string $semester = 'Ganjil'): array
    {
        return $this->syncScoresForClassSubject($kelasId, $mapelId, 'sas', null, null, $tahunAjaran, $semester);
    }

    /**
     * Tarik nilai fleksibel (SAS, STS / Tengah Semester, atau Asesmen Harian) dari Exam Web ke E-Rapor
     */
    public function syncScoresForClassSubject(
        int $kelasId,
        int $mapelId,
        string $targetType = 'sas',
        ?int $ujianId = null,
        ?int $sumatifIndex = null,
        string $tahunAjaran = '2025/2026',
        string $semester = '2'
    ): array {
        if (!$this->checkConnection()) {
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke database CBT Exam Web (exam_web).',
                'updated_count' => 0,
            ];
        }

        $kelas = Kelas::find($kelasId);
        $mapel = Mapel::find($mapelId);

        if (!$kelas || !$mapel) {
            return [
                'success' => false,
                'message' => 'Data Kelas atau Mata Pelajaran tidak ditemukan di E-Rapor.',
                'updated_count' => 0,
            ];
        }

        // Cari guru pengampu di kelas & mapel ini
        $gmk = GuruMapelKelas::where('kelas_id', $kelasId)
            ->where('mapel_id', $mapelId)
            ->first();

        $guruId = $gmk ? $gmk->guru_id : 1;
        $jumlahSumatif = $gmk && $gmk->jumlah_sumatif ? $gmk->jumlah_sumatif : 3;
        $bobotHarian = $gmk && isset($gmk->bobot_harian) ? (int)$gmk->bobot_harian : 50;
        $bobotSts = $gmk && isset($gmk->bobot_sts) ? (int)$gmk->bobot_sts : 20;
        $bobotSas = $gmk && isset($gmk->bobot_sas) ? (int)$gmk->bobot_sas : 30;

        // Cari mapel di exam_web berdasarkan sync_id atau nama
        $examMapel = null;
        if (!empty($mapel->sync_id)) {
            $examMapel = DB::connection('exam_db')->table('mapels')
                ->where('sync_id', $mapel->sync_id)
                ->first();
        }
        if (!$examMapel) {
            $examMapel = DB::connection('exam_db')->table('mapels')
                ->where('nama_mapel', 'LIKE', '%' . trim($mapel->nama_mapel) . '%')
                ->first();
        }

        if (!$examMapel) {
            return [
                'success' => false,
                'message' => "Mata pelajaran '{$mapel->nama_mapel}' belum ada di Exam Web.",
                'updated_count' => 0,
            ];
        }

        // Tentukan ujian_id yang diambil
        $ujianIds = [];
        if ($ujianId) {
            $ujianIds = [$ujianId];
        } else {
            if ($targetType === 'sas') {
                $ujianIds = DB::connection('exam_db')->table('ujians')
                    ->where('mapel_id', $examMapel->id)
                    ->where(function ($q) {
                        $q->where('tipe_ujian', 'asesmen_akhir_semester')
                          ->orWhere('exam_id', 'LIKE', 'SAS-%');
                    })
                    ->pluck('id')
                    ->toArray();
            } elseif ($targetType === 'sts') {
                $ujianIds = DB::connection('exam_db')->table('ujians')
                    ->where('mapel_id', $examMapel->id)
                    ->where(function ($q) {
                        $q->where('tipe_ujian', 'asesmen_tengah_semester')
                          ->orWhere('exam_id', 'LIKE', 'STS-%')
                          ->orWhere('exam_id', 'LIKE', 'PTS-%')
                          ->orWhere('nama_ujian', 'LIKE', '%STS%')
                          ->orWhere('nama_ujian', 'LIKE', '%Tengah Semester%')
                          ->orWhere('nama_ujian', 'LIKE', '%PTS%');
                    })
                    ->pluck('id')
                    ->toArray();
            } elseif ($targetType === 'harian') {
                $ujianIds = DB::connection('exam_db')->table('ujians')
                    ->where('mapel_id', $examMapel->id)
                    ->where(function ($q) {
                        $q->where('tipe_ujian', 'asesmen_harian')
                          ->orWhere('exam_id', 'LIKE', 'SH-%')
                          ->orWhere('nama_ujian', 'LIKE', '%Harian%');
                    })
                    ->pluck('id')
                    ->toArray();
            }
        }

        if (empty($ujianIds)) {
            $labelType = match($targetType) {
                'sts' => 'Sumatif Tengah Semester (STS)',
                'harian' => 'Asesmen Harian (SH)',
                default => 'Ujian Akhir Semester (SAS)',
            };
            return [
                'success' => false,
                'message' => "Belum ada pelaksanaan ujian {$labelType} untuk mata pelajaran '{$mapel->nama_mapel}' di CBT Exam Web.",
                'updated_count' => 0,
            ];
        }

        // Ambil semua siswa di rombel ini
        $siswas = Siswa::where('kelas_id', $kelasId)->get();
        if ($siswas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada siswa di rombel ini.',
                'updated_count' => 0,
            ];
        }

        $nisList = $siswas->pluck('nis')->filter()->values()->toArray();
        $syncIdList = $siswas->pluck('sync_id')->filter()->values()->toArray();

        // Cari siswa_id di exam_web yang cocok dengan NIS atau sync_id siswa rombel ini
        $examSiswas = DB::connection('exam_db')->table('siswas')
            ->where(function ($q) use ($nisList, $syncIdList) {
                if (!empty($nisList)) {
                    $q->whereIn('nis', $nisList);
                }
                if (!empty($syncIdList)) {
                    $q->orWhereIn('sync_id', $syncIdList);
                }
            })
            ->get(['id', 'nis', 'sync_id'])
            ->keyBy('id');

        if ($examSiswas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Data siswa rombel ini tidak terdaftar di database CBT Exam Web.',
                'updated_count' => 0,
            ];
        }

        // Ambil nilai ujian_pesertas
        $pesertaResults = DB::connection('exam_db')->table('ujian_pesertas')
            ->whereIn('ujian_id', $ujianIds)
            ->whereIn('siswa_id', $examSiswas->keys()->toArray())
            ->where('status', 'SELESAI')
            ->whereNotNull('nilai')
            ->orderBy('id', 'desc')
            ->get();

        if ($pesertaResults->isEmpty()) {
            return [
                'success' => false,
                'message' => "Belum ada siswa di kelas {$kelas->nama_kelas} yang menyelesaikan ujian ini di CBT Exam Web.",
                'updated_count' => 0,
            ];
        }

        // Map exam siswa id to e-rapor siswa
        $siswaByNis = $siswas->keyBy('nis');
        $siswaBySyncId = $siswas->whereNotNull('sync_id')->keyBy('sync_id');

        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($pesertaResults as $res) {
                $examSiswa = $examSiswas->get($res->siswa_id);
                if (!$examSiswa) continue;

                $targetSiswa = null;
                if (!empty($examSiswa->nis) && $siswaByNis->has($examSiswa->nis)) {
                    $targetSiswa = $siswaByNis->get($examSiswa->nis);
                } elseif (!empty($examSiswa->sync_id) && $siswaBySyncId->has($examSiswa->sync_id)) {
                    $targetSiswa = $siswaBySyncId->get($examSiswa->sync_id);
                }

                if (!$targetSiswa) continue;

                $nilaiRec = Nilai::firstOrNew([
                    'siswa_id' => $targetSiswa->id,
                    'kelas_id' => $kelasId,
                    'mapel_id' => $mapelId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $semester,
                ]);

                $nilaiRec->school_id = $targetSiswa->school_id ?? $kelas->school_id;
                $nilaiRec->guru_id = $guruId;

                $scoreVal = (float) $res->nilai;

                // Terapkan nilai ke target kolom yang sesuai
                if ($targetType === 'sts') {
                    $nilaiRec->nilai_uts = $scoreVal; // STS
                } elseif ($targetType === 'harian') {
                    $idx = ($sumatifIndex >= 1 && $sumatifIndex <= 6) ? $sumatifIndex : 1;
                    $nilaiRec->{"sumatif_{$idx}"} = $scoreVal;

                    // Hitung ulang rata-rata sumatif harian
                    $validSumatifs = [];
                    for ($s = 1; $s <= $jumlahSumatif; $s++) {
                        $val = $nilaiRec->{"sumatif_{$s}"};
                        if ($val !== null && $val !== '') {
                            $validSumatifs[] = (float) $val;
                        }
                    }
                    if (count($validSumatifs) > 0) {
                        $nilaiRec->rata_sumatif_harian = round(array_sum($validSumatifs) / count($validSumatifs), 1);
                        $nilaiRec->nilai_tugas = $nilaiRec->rata_sumatif_harian;
                    }
                } else {
                    // Default SAS
                    $nilaiRec->nilai_uas = $scoreVal;
                }

                // Kalkulasi Nilai Akhir (NA) Komprehensif (Harian + STS + SAS)
                $rslm = $nilaiRec->rata_sumatif_harian;
                $sts = $nilaiRec->nilai_uts;
                $sas = $nilaiRec->nilai_uas;

                $weightedSum = 0;
                $totalActiveWeight = 0;

                if ($rslm !== null && $rslm !== '' && $bobotHarian > 0) {
                    $weightedSum += ($rslm * $bobotHarian);
                    $totalActiveWeight += $bobotHarian;
                }
                if ($sts !== null && $sts !== '' && $bobotSts > 0) {
                    $weightedSum += ($sts * $bobotSts);
                    $totalActiveWeight += $bobotSts;
                }
                if ($sas !== null && $sas !== '' && $bobotSas > 0) {
                    $weightedSum += ($sas * $bobotSas);
                    $totalActiveWeight += $bobotSas;
                }

                if ($totalActiveWeight > 0) {
                    $nilaiRec->nilai_akhir = round($weightedSum / $totalActiveWeight, 1);
                }

                $nilaiRec->save();
                $updatedCount++;
            }

            DB::commit();

            $targetLabel = match($targetType) {
                'sts' => 'nilai STS (Sumatif Tengah Semester)',
                'harian' => "nilai Sumatif Harian ke-{$sumatifIndex}",
                default => 'nilai SAS (Asesmen Akhir Semester)',
            };

            return [
                'success' => true,
                'message' => "Berhasil menyinkronkan {$updatedCount} {$targetLabel} dari CBT Exam Web untuk kelas {$kelas->nama_kelas}.",
                'updated_count' => $updatedCount,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gagal syncScoresForClassSubject: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan nilai: ' . $e->getMessage(),
                'updated_count' => 0,
            ];
        }
    }

    /**
     * Sinkronisasi seluruh nilai SAS untuk seluruh kelas dan mapel sekolah
     */
    public function syncAllSasScores(?int $schoolId = null): array
    {
        if (!$this->checkConnection()) {
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke database CBT Exam Web (exam_web).',
                'total_synced' => 0,
            ];
        }

        $school = null;
        if ($schoolId) {
            $school = School::find($schoolId);
        } else {
            $school = School::first();
        }

        if (!$school) {
            return [
                'success' => false,
                'message' => 'Data sekolah belum tersedia.',
                'total_synced' => 0,
            ];
        }

        // Ambil semua kelas di sekolah ini
        $kelases = Kelas::where('school_id', $school->id)->get();
        $mapels = Mapel::where('school_id', $school->id)->get();

        $totalSynced = 0;
        $errors = [];

        foreach ($kelases as $kelas) {
            foreach ($mapels as $mapel) {
                $res = $this->syncSasForClassSubject($kelas->id, $mapel->id);
                if ($res['success']) {
                    $totalSynced += $res['updated_count'];
                }
            }
        }

        // Catat log
        SyncLog::create([
            'source_url' => 'exam_web (CBT MySQL)',
            'status' => 'success',
            'message' => "Sinkronisasi nilai SAS massal dari Exam Web. {$totalSynced} nilai berhasil diperbarui.",
        ]);

        return [
            'success' => true,
            'message' => "Sinkronisasi selesai! Total {$totalSynced} nilai SAS berhasil ditarik dari Exam Web.",
            'total_synced' => $totalSynced,
        ];
    }
}
