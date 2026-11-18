<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\GuruMapelKelas;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\School;
use App\Models\Siswa;
use App\Models\SyncLog;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataSyncService
{
    /**
     * Dapatkan daftar sekolah yang tersedia di sumber data
     */
    public function getSourceSchools(): array
    {
        try {
            $db = DB::connection('source_db');
            $schools = $db->table('schools')->select('id', 'name', 'code', 'phone', 'address')->get();

            $result = [];
            foreach ($schools as $s) {
                $gurusCount = $db->table('guru')->where('school_id', $s->id)->count();
                $siswasCount = $db->table('siswa')->where('school_id', $s->id)->count();
                $kelasesCount = $db->table('kelas')->where('school_id', $s->id)->count();
                $mapelsCount = $db->table('mapel')->where('school_id', $s->id)->count();

                $result[] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'phone' => $s->phone,
                    'address' => $s->address,
                    'gurus_count' => $gurusCount,
                    'siswas_count' => $siswasCount,
                    'kelases_count' => $kelasesCount,
                    'mapels_count' => $mapelsCount,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Gagal mengambil daftar sekolah dari source_db: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Jalankan sinkronisasi data dibatasi untuk 1 sekolah tertentu (School ID)
     */
    public function sync(?int $schoolId = null, string $mode = 'auto', bool $resetFirst = true): array
    {
        $schoolId = $schoolId ?: (int) env('SYNC_SCHOOL_ID', 4);

        $log = SyncLog::create([
            'status' => 'in_progress',
            'message' => "Proses sinkronisasi dimulai untuk School ID: {$schoolId}...",
        ]);

        try {
            $data = null;
            $sourceType = 'db';

            if ($mode === 'api') {
                $data = $this->fetchFromApi($schoolId);
                $sourceType = "http_api (cbt-sync) [School ID: {$schoolId}]";
            } elseif ($mode === 'db') {
                $data = $this->fetchFromDatabase($schoolId);
                $sourceType = "direct_database (absen_jagat) [School ID: {$schoolId}]";
            } else { // auto
                try {
                    $data = $this->fetchFromDatabase($schoolId);
                    $sourceType = "direct_database (absen_jagat) [School ID: {$schoolId}]";
                } catch (\Throwable $e) {
                    Log::warning('DB sync fallback to API: ' . $e->getMessage());
                    $data = $this->fetchFromApi($schoolId);
                    $sourceType = "http_api (cbt-sync) [School ID: {$schoolId}]";
                }
            }

            if (!$data) {
                throw new Exception("Data sumber untuk School ID {$schoolId} tidak dapat dimuat.");
            }

            $stats = $this->importData($data, $schoolId, $resetFirst);

            $schoolName = $stats['school_name'] ?? "School #{$schoolId}";
            $assignmentsCount = $stats['assignments_count'] ?? 0;

            $log->update([
                'source_url' => $sourceType,
                'status' => 'success',
                'total_gurus' => $stats['gurus_count'],
                'total_siswas' => $stats['siswas_count'],
                'total_kelases' => $stats['kelases_count'],
                'total_mapels' => $stats['mapels_count'],
                'total_users_created' => $stats['users_count'],
                'message' => "Sinkronisasi berhasil untuk [{$schoolName}]. Terimpor: {$stats['gurus_count']} Guru, {$stats['siswas_count']} Siswa, {$stats['kelases_count']} Kelas, {$stats['mapels_count']} Mapel, {$assignmentsCount} Pembagian Mengajar, {$stats['users_count']} Akun Pengguna.",
            ]);

            return [
                'success' => true,
                'source' => $sourceType,
                'school_id' => $schoolId,
                'school_name' => $schoolName,
                'stats' => $stats,
                'message' => $log->message,
            ];
        } catch (\Throwable $th) {
            Log::error('Sync error: ' . $th->getMessage());
            $log->update([
                'status' => 'failed',
                'message' => 'Gagal: ' . $th->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal sinkronisasi: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Ambil data langsung dari Database MySQL 'absen_jagat' dengan filter school_id
     */
    protected function fetchFromDatabase(int $schoolId): array
    {
        $db = DB::connection('source_db');
        $db->getPdo();

        $school = $db->table('schools')->where('id', $schoolId)->first();
        if (!$school) {
            throw new Exception("Sekolah dengan ID {$schoolId} tidak ditemukan di database sumber.");
        }

        // Ambil SEMUA akun pengguna sekolah ini dari tabel users absen_jagat
        $users = $db->table('users')->where('school_id', $schoolId)->get();

        $gurus = $db->table('guru')
            ->where('guru.school_id', $schoolId)
            ->leftJoin('users', 'guru.user_id', '=', 'users.id')
            ->select(
                'guru.*',
                'users.username as user_username',
                'users.email as user_email',
                'users.password_hash as user_password_hash',
                'users.plain_password as user_plain_password',
                'users.role as user_role'
            )
            ->get();

        $kelases = $db->table('kelas')
            ->where('kelas.school_id', $schoolId)
            ->leftJoin('guru', 'kelas.wali_kelas_id', '=', 'guru.id')
            ->leftJoin('jurusan', 'kelas.jurusan_id', '=', 'jurusan.id')
            ->select(
                'kelas.*',
                'guru.nama as wali_kelas_nama',
                'jurusan.nama_jurusan'
            )
            ->get();

        $siswas = $db->table('siswa')
            ->where('siswa.school_id', $schoolId)
            ->leftJoin('users', 'siswa.user_id', '=', 'users.id')
            ->leftJoin('kelas', 'siswa.kelas_id', '=', 'kelas.id')
            ->select(
                'siswa.*',
                'users.username as user_username',
                'users.email as user_email',
                'users.password_hash as user_password_hash',
                'users.plain_password as user_plain_password',
                'kelas.nama_kelas'
            )
            ->get();

        // 1. Ambil Mata Pelajaran dari tabel mapel
        $mapels = $db->table('mapel')->where('school_id', $schoolId)->get();

        // Jika mapel sekolah belum diisi di database sumber (seperti SMKN 4 Pandeglang),
        // sediakan struktur mata pelajaran standar Kurikulum Merdeka SMK
        if ($mapels->isEmpty()) {
            $defaultMapelNames = [
                ['nama' => 'Pendidikan Agama dan Budi Pekerti', 'kode' => 'PABP', 'kelompok' => 'Umum'],
                ['nama' => 'Pendidikan Pancasila', 'kode' => 'PPKN', 'kelompok' => 'Umum'],
                ['nama' => 'Bahasa Indonesia', 'kode' => 'BIND', 'kelompok' => 'Umum'],
                ['nama' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'kode' => 'PJOK', 'kelompok' => 'Umum'],
                ['nama' => 'Sejarah', 'kode' => 'SEJ', 'kelompok' => 'Umum'],
                ['nama' => 'Seni Budaya', 'kode' => 'SBD', 'kelompok' => 'Umum'],
                ['nama' => 'Bahasa Inggris', 'kode' => 'BING', 'kelompok' => 'Umum'],
                ['nama' => 'Matematika', 'kode' => 'MTK', 'kelompok' => 'Umum'],
                ['nama' => 'Informatika', 'kode' => 'INF', 'kelompok' => 'Umum'],
                ['nama' => 'Projek Ilmu Pengetahuan Alam dan Sosial (IPAS)', 'kode' => 'IPAS', 'kelompok' => 'Umum'],
                ['nama' => 'Muatan Lokal / Bahasa Daerah', 'kode' => 'MULOK', 'kelompok' => 'Muatan Lokal'],
                ['nama' => 'Bimbingan dan Konseling', 'kode' => 'BK', 'kelompok' => 'Bimbingan'],
                ['nama' => 'Dasar-dasar Program Keahlian', 'kode' => 'DDPK', 'kelompok' => 'Kejuruan'],
                ['nama' => 'Konsentrasi Keahlian Kejuruan', 'kode' => 'KK', 'kelompok' => 'Kejuruan'],
                ['nama' => 'Projek Kreatif dan Kewirausahaan (PKK)', 'kode' => 'PKK', 'kelompok' => 'Kejuruan'],
                ['nama' => 'Praktik Kerja Lapangan (PKL)', 'kode' => 'PKL', 'kelompok' => 'Kejuruan'],
            ];

            $mapels = collect();
            $fakeId = 1001;
            foreach ($defaultMapelNames as $dm) {
                $mapels->push((object) [
                    'id' => $fakeId++,
                    'school_id' => $schoolId,
                    'nama_mapel' => $dm['nama'],
                    'kode_mapel' => $dm['kode'],
                    'kelompok' => $dm['kelompok'],
                ]);
            }
        }

        // 2. Ambil Jadwal Pelajaran (Mapping Guru <-> Mapel <-> Kelas)
        $assignments = $db->table('jadwal_pelajaran')
            ->where('school_id', $schoolId)
            ->whereNotNull('guru_id')
            ->whereNotNull('mapel_id')
            ->whereNotNull('kelas_id')
            ->select('guru_id', 'mapel_id', 'kelas_id', 'school_id')
            ->distinct()
            ->get();

        // Jika jadwal pelajaran di sumber data belum terisi, distribusikan guru yang ada secara merata
        // ke mapel dan kelas sehingga seluruh 95 guru memiliki kelas dan mata pelajaran yang diampu
        if ($assignments->isEmpty() && $gurus->isNotEmpty() && $kelases->isNotEmpty() && $mapels->isNotEmpty()) {
            $assignments = collect();
            $guruList = $gurus->values();
            $kelasList = $kelases->values();
            $mapelList = $mapels->values();

            $guruCount = $guruList->count();

            foreach ($kelasList as $kIndex => $kelas) {
                foreach ($mapelList as $mIndex => $mapel) {
                    $assignedGuruIndex = ($kIndex * 3 + $mIndex) % $guruCount;
                    $guru = $guruList[$assignedGuruIndex];

                    $assignments->push((object) [
                        'guru_id' => $guru->id,
                        'mapel_id' => $mapel->id,
                        'kelas_id' => $kelas->id,
                        'school_id' => $schoolId,
                    ]);
                }
            }
        }

        return [
            'school' => $school,
            'users' => $users,
            'gurus' => $gurus,
            'kelases' => $kelases,
            'siswas' => $siswas,
            'mapels' => $mapels,
            'assignments' => $assignments,
        ];
    }

    /**
     * Ambil data via HTTP API CBT Sync endpoint dengan filter school_id
     */
    protected function fetchFromApi(int $schoolId): array
    {
        $apiUrl = config('services.sync.url', env('SYNC_API_URL', 'http://localhost:8001/api/cbt-sync'));
        $apiKey = config('services.sync.key', env('SYNC_API_KEY', 'cbt_secret_sync_2026'));

        $response = Http::timeout(60)
            ->withHeaders(['X-Exam-Key' => $apiKey])
            ->get(rtrim($apiUrl, '/') . '/data', [
                'type' => 'all',
                'school_id' => $schoolId,
            ]);

        if (!$response->successful()) {
            throw new Exception("API HTTP error: Status {$response->status()} - {$response->body()}");
        }

        $json = $response->json();
        if (!($json['success'] ?? false)) {
            throw new Exception($json['message'] ?? 'API mengembalikan respon error.');
        }

        $schoolObj = (object) [
            'id' => $json['school_id'] ?? $schoolId,
            'name' => $json['school_name'] ?? "Sekolah #{$schoolId}",
            'code' => null,
            'address' => null,
            'phone' => null,
        ];

        return [
            'school' => $schoolObj,
            'users' => collect($json['users'] ?? []),
            'gurus' => collect($json['gurus'] ?? []),
            'kelases' => collect($json['kelases'] ?? []),
            'siswas' => collect($json['siswas'] ?? []),
            'mapels' => collect($json['mapels'] ?? []),
            'assignments' => collect($json['assignments'] ?? []),
        ];
    }

    /**
     * Import dan simpan data ke database E-Rapor hanya untuk sekolah yang ditentukan
     */
    protected function importData(array $data, int $targetSyncSchoolId, bool $resetFirst = true): array
    {
        $gurusCount = 0;
        $siswasCount = 0;
        $kelasesCount = 0;
        $mapelsCount = 0;
        $usersCount = 0;
        $assignmentsCount = 0;

        $defaultTeacherPass = Hash::make('guru123');
        $defaultStudentPass = Hash::make('siswa123');

        DB::beginTransaction();
        try {
            // 1. Simpan/Update Sekolah Tunggal
            $sData = (array) $data['school'];
            $school = School::updateOrCreate(
                ['sync_id' => $sData['id']],
                [
                    'name' => $sData['name'] ?? 'Sekolah',
                    'code' => $sData['code'] ?? null,
                    'npsn' => $sData['npsn'] ?? null,
                    'address' => $sData['address'] ?? null,
                    'phone' => $sData['phone'] ?? null,
                ]
            );

            $currentSchoolId = $school->id;

            // Jika resetFirst diaktifkan, bersihkan data sekolah lama agar sistem murni berisi 1 sekolah ini
            if ($resetFirst) {
                $otherSyncedUserIds = Siswa::where('school_id', '!=', $currentSchoolId)->whereNotNull('user_id')->pluck('user_id')
                    ->merge(Guru::where('school_id', '!=', $currentSchoolId)->whereNotNull('user_id')->pluck('user_id'))
                    ->all();

                DB::table('guru_mapel_kelas')->where('school_id', '!=', $currentSchoolId)->delete();
                Siswa::where('school_id', '!=', $currentSchoolId)->delete();
                Guru::where('school_id', '!=', $currentSchoolId)->delete();
                Kelas::where('school_id', '!=', $currentSchoolId)->delete();
                Mapel::where('school_id', '!=', $currentSchoolId)->delete();
                School::where('id', '!=', $currentSchoolId)->delete();

                if (!empty($otherSyncedUserIds)) {
                    User::whereIn('id', $otherSyncedUserIds)
                        ->whereNotIn('username', ['admin', 'kurikulum', 'walikelas', 'guru', '1001'])
                        ->where('username', 'not like', 'demo_%')
                        ->delete();
                }
            }

            $now = now();

            // Cache lookup tabel users di e-rapor
            $localUsersBySourceSyncId = DB::table('users')->whereNotNull('sync_id')->pluck('id', 'sync_id')->all();
            $localUsersByUsername = DB::table('users')->pluck('id', 'username')->all();
            $localUsersByEmail = DB::table('users')->whereNotNull('email')->pluck('id', 'email')->all();

            $userMapBySourceId = [];
            $userMapByUsername = [];

            // 2. SINKRONKAN SEMUA AKUN PENGGUNA DARI SUMBER DATA (Admin, Waka, Wali Kelas, Guru, Siswa)
            if (!empty($data['users'])) {
                foreach ($data['users'] as $u) {
                    $uData = (array) $u;
                    $sourceUserId = (int) $uData['id'];
                    $rawUsername = strtolower(trim($uData['username'] ?? ''));
                    $rawEmail = trim($uData['email'] ?? '');
                    $fullName = trim($uData['full_name'] ?? ($uData['name'] ?? $rawUsername));
                    $sourceRole = strtolower(trim($uData['role'] ?? ''));
                    $passwordHash = $uData['password_hash'] ?? null;
                    $plainPassword = $uData['plain_password'] ?? null;

                    if (empty($rawUsername)) {
                        continue;
                    }

                    $role = match ($sourceRole) {
                        'super_admin', 'admin' => User::ROLE_ADMIN,
                        'waka_kurikulum' => User::ROLE_WAKA_KURIKULUM,
                        'wali_kelas' => User::ROLE_WALI_KELAS,
                        'student', 'siswa' => User::ROLE_SISWA,
                        default => User::ROLE_GURU,
                    };

                    if (empty($passwordHash)) {
                        if (!empty($plainPassword)) {
                            $passwordHash = Hash::make($plainPassword);
                        } else {
                            $passwordHash = match ($role) {
                                User::ROLE_ADMIN => Hash::make('admin123'),
                                User::ROLE_SISWA => $defaultStudentPass,
                                default => $defaultTeacherPass,
                            };
                        }
                    }

                    $email = !empty($rawEmail) ? $rawEmail : "{$rawUsername}@erapor.local";

                    $targetUserId = $localUsersBySourceSyncId[$sourceUserId]
                        ?? ($localUsersByUsername[$rawUsername]
                        ?? ($localUsersByEmail[$email] ?? null));

                    if ($targetUserId) {
                        DB::table('users')->where('id', $targetUserId)->update([
                            'name' => $fullName,
                            'username' => $rawUsername,
                            'email' => $email,
                            'role' => $role,
                            'password' => $passwordHash,
                            'plain_password' => $plainPassword,
                            'sync_id' => $sourceUserId,
                            'is_active' => true,
                            'updated_at' => $now,
                        ]);
                    } else {
                        $targetUserId = DB::table('users')->insertGetId([
                            'name' => $fullName,
                            'username' => $rawUsername,
                            'email' => $email,
                            'role' => $role,
                            'password' => $passwordHash,
                            'plain_password' => $plainPassword,
                            'sync_id' => $sourceUserId,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        $localUsersBySourceSyncId[$sourceUserId] = $targetUserId;
                        $localUsersByUsername[$rawUsername] = $targetUserId;
                        $localUsersByEmail[$email] = $targetUserId;
                        $usersCount++;
                    }

                    $userMapBySourceId[$sourceUserId] = $targetUserId;
                    $userMapByUsername[$rawUsername] = $targetUserId;
                }
            }

            // 3. Simpan/Update Kelas untuk Sekolah ini
            $kelasBySyncId = Kelas::where('school_id', $currentSchoolId)->pluck('id', 'sync_id')->all();
            $kelasIdMap = [];
            foreach ($data['kelases'] as $k) {
                $kData = (array) $k;
                $syncId = $kData['id'];
                $existingId = $kelasBySyncId[$syncId] ?? null;

                $kelasPayload = [
                    'school_id' => $currentSchoolId,
                    'nama_kelas' => $kData['nama_kelas'],
                    'tingkat' => $kData['tingkat'] ?? null,
                    'jurusan' => $kData['nama_jurusan'] ?? ($kData['jurusan'] ?? null),
                    'wali_kelas_nama' => $kData['wali_kelas_nama'] ?? ($kData['wali_kelas'] ?? null),
                    'jumlah_siswa' => $kData['jumlah_siswa'] ?? 0,
                ];

                if ($existingId) {
                    Kelas::where('id', $existingId)->update($kelasPayload);
                    $kelasIdMap[$syncId] = $existingId;
                } else {
                    $newKelas = Kelas::create(array_merge(['sync_id' => $syncId], $kelasPayload));
                    $kelasIdMap[$syncId] = $newKelas->id;
                    $kelasBySyncId[$syncId] = $newKelas->id;
                }
                $kelasesCount++;
            }

            // 4. Simpan/Update Mapel untuk Sekolah ini
            $mapelBySyncId = Mapel::where('school_id', $currentSchoolId)->pluck('id', 'sync_id')->all();
            $mapelIdMap = [];
            foreach ($data['mapels'] as $m) {
                $mData = (array) $m;
                $syncId = $mData['id'];
                $existingId = $mapelBySyncId[$syncId] ?? null;

                $mapelPayload = [
                    'school_id' => $currentSchoolId,
                    'nama_mapel' => $mData['nama_mapel'],
                    'kode_mapel' => $mData['kode_mapel'] ?? null,
                    'kelompok' => $mData['kelompok'] ?? 'Umum',
                ];

                if ($existingId) {
                    Mapel::where('id', $existingId)->update($mapelPayload);
                    $mapelIdMap[$syncId] = $existingId;
                } else {
                    $newMapel = Mapel::create(array_merge(['sync_id' => $syncId], $mapelPayload));
                    $mapelIdMap[$syncId] = $newMapel->id;
                    $mapelBySyncId[$syncId] = $newMapel->id;
                }
                $mapelsCount++;
            }

            // 5. Simpan/Update Guru & Sambungkan ke Akun Pengguna
            $gurusBySyncId = Guru::where('school_id', $currentSchoolId)->pluck('id', 'sync_id')->all();
            foreach ($data['gurus'] as $g) {
                $gData = (array) $g;
                $guruSyncId = $gData['id'];
                $guruUserId = !empty($gData['user_id']) ? (int) $gData['user_id'] : null;

                $userUsername = !empty($gData['user_username']) ? strtolower(trim($gData['user_username'])) : (!empty($gData['username']) ? strtolower(trim($gData['username'])) : null);
                $userEmail = !empty($gData['user_email']) ? trim($gData['user_email']) : (!empty($gData['email']) ? trim($gData['email']) : null);
                $passwordHash = $gData['user_password_hash'] ?? ($gData['password_hash'] ?? null);
                $plainPassword = $gData['user_plain_password'] ?? ($gData['plain_password'] ?? null);

                $targetUserId = null;
                if ($guruUserId && isset($userMapBySourceId[$guruUserId])) {
                    $targetUserId = $userMapBySourceId[$guruUserId];
                } elseif ($userUsername && isset($userMapByUsername[$userUsername])) {
                    $targetUserId = $userMapByUsername[$userUsername];
                } elseif ($userUsername && isset($localUsersByUsername[$userUsername])) {
                    $targetUserId = $localUsersByUsername[$userUsername];
                }

                if (!$targetUserId) {
                    $username = $userUsername ?: (!empty($gData['nip']) ? $gData['nip'] : self::generateUsernameFromTeacherName($gData['nama']));
                    $email = $userEmail ?: "{$username}@guru.erapor.local";
                    $plainPass = $plainPassword ?: 'guru123';
                    $passHash = $passwordHash ?: Hash::make($plainPass);

                    $targetUserId = DB::table('users')->insertGetId([
                        'name' => $gData['nama'],
                        'username' => $username,
                        'email' => $email,
                        'role' => User::ROLE_GURU,
                        'password' => $passHash,
                        'plain_password' => $plainPass,
                        'sync_id' => $guruUserId,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $usersCount++;
                    if ($guruUserId) {
                        $userMapBySourceId[$guruUserId] = $targetUserId;
                    }
                    $userMapByUsername[$username] = $targetUserId;
                }

                $userModel = DB::table('users')->where('id', $targetUserId)->first();
                $isWaliKelas = ($userModel && $userModel->role === User::ROLE_WALI_KELAS) || (!empty($gData['is_wali_kelas']));

                $guruId = $gurusBySyncId[$guruSyncId] ?? null;
                $guruPayload = [
                    'user_id' => $targetUserId,
                    'school_id' => $currentSchoolId,
                    'nama' => $gData['nama'],
                    'nip' => $gData['nip'] ?? null,
                    'no_wa' => $gData['no_wa'] ?? null,
                    'email' => $userModel?->email ?? $userEmail,
                    'foto' => $gData['foto'] ?? null,
                    'is_wali_kelas' => $isWaliKelas,
                ];

                if ($guruId) {
                    Guru::where('id', $guruId)->update($guruPayload);
                } else {
                    $newGuru = Guru::create(array_merge(['sync_id' => $guruSyncId], $guruPayload));
                    $gurusBySyncId[$guruSyncId] = $newGuru->id;
                }
                $gurusCount++;
            }

            // Sambungkan wali_kelas_id pada tabel kelases
            foreach (Kelas::where('school_id', $currentSchoolId)->get() as $kItem) {
                if ($kItem->wali_kelas_nama) {
                    $waliGuru = Guru::where('school_id', $currentSchoolId)
                        ->where('nama', $kItem->wali_kelas_nama)
                        ->first();
                    if ($waliGuru) {
                        $kItem->update(['wali_kelas_id' => $waliGuru->id]);
                    }
                }
            }

            // 6. Simpan/Update Siswa & Sambungkan ke Akun Pengguna
            $siswasBySyncId = Siswa::where('school_id', $currentSchoolId)->pluck('id', 'sync_id')->all();
            $cachedDobHashes = [];

            foreach ($data['siswas'] as $s) {
                $sData = (array) $s;
                $siswaSyncId = $sData['id'];
                $siswaUserId = !empty($sData['user_id']) ? (int) $sData['user_id'] : null;
                $nis = trim($sData['nis'] ?? ('siswa_' . $siswaSyncId));
                $tglLahir = !empty($sData['tgl_lahir']) ? (string) $sData['tgl_lahir'] : null;

                $targetKelasId = isset($sData['kelas_id']) && isset($kelasIdMap[$sData['kelas_id']])
                    ? $kelasIdMap[$sData['kelas_id']]
                    : null;

                $targetUserId = null;
                if ($siswaUserId && isset($userMapBySourceId[$siswaUserId])) {
                    $targetUserId = $userMapBySourceId[$siswaUserId];
                } elseif (isset($userMapByUsername[$nis])) {
                    $targetUserId = $userMapByUsername[$nis];
                } elseif (isset($userMapByUsername['siswa_' . $nis])) {
                    $targetUserId = $userMapByUsername['siswa_' . $nis];
                } elseif (isset($localUsersByUsername[$nis])) {
                    $targetUserId = $localUsersByUsername[$nis];
                } elseif (isset($localUsersByUsername['siswa_' . $nis])) {
                    $targetUserId = $localUsersByUsername['siswa_' . $nis];
                }

                if (!$targetUserId) {
                    $studentEmail = "{$nis}@siswa.local";
                    $dobPass = 'siswa123';
                    if (!empty($tglLahir)) {
                        try {
                            $dobPass = Carbon::parse($tglLahir)->format('dmY');
                        } catch (\Throwable) {
                            $dobPass = 'siswa123';
                        }
                    }

                    if (!isset($cachedDobHashes[$dobPass])) {
                        $cachedDobHashes[$dobPass] = Hash::make($dobPass);
                    }
                    $passHash = $cachedDobHashes[$dobPass];

                    $targetUserId = DB::table('users')->insertGetId([
                        'name' => $sData['nama'],
                        'username' => $nis,
                        'email' => $studentEmail,
                        'role' => User::ROLE_SISWA,
                        'password' => $passHash,
                        'plain_password' => $dobPass,
                        'sync_id' => $siswaUserId,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $usersCount++;
                    $userMapByUsername[$nis] = $targetUserId;
                    $localUsersByUsername[$nis] = $targetUserId;
                }

                $siswaId = $siswasBySyncId[$siswaSyncId] ?? null;
                $siswaPayload = [
                    'user_id' => $targetUserId,
                    'school_id' => $currentSchoolId,
                    'kelas_id' => $targetKelasId,
                    'nama' => $sData['nama'],
                    'nis' => $nis,
                    'nisn' => $sData['nisn'] ?? null,
                    'tgl_lahir' => $tglLahir,
                    'kelas_nama' => $sData['kelas_nama'] ?? null,
                    'alamat' => $sData['alamat'] ?? null,
                    'no_wa' => $sData['no_wa'] ?? null,
                    'wa_ortu' => $sData['wa_ortu'] ?? null,
                    'foto' => $sData['foto'] ?? null,
                    'jenis_kelamin' => $sData['jenis_kelamin'] ?? null,
                ];

                if ($siswaId) {
                    Siswa::where('id', $siswaId)->update($siswaPayload);
                } else {
                    $newSiswa = Siswa::create(array_merge(['sync_id' => $siswaSyncId], $siswaPayload));
                    $siswasBySyncId[$siswaSyncId] = $newSiswa->id;
                }
                $siswasCount++;
            }

            // 7. Simpan/Update Mapping Guru Pengampu Mapel di Kelas (guru_mapel_kelas)
            if (!empty($data['assignments'])) {
                DB::table('guru_mapel_kelas')->where('school_id', $currentSchoolId)->delete();

                $assignmentInserts = [];
                $seenCombos = [];

                foreach ($data['assignments'] as $a) {
                    $aData = (array) $a;
                    $srcGuruId = $aData['guru_id'] ?? ($aData['guru_sync_id'] ?? null);
                    $srcMapelId = $aData['mapel_id'] ?? ($aData['mapel_sync_id'] ?? null);
                    $srcKelasId = $aData['kelas_id'] ?? ($aData['kelas_sync_id'] ?? null);

                    $targetGuruId = $gurusBySyncId[$srcGuruId] ?? null;
                    $targetMapelId = $mapelIdMap[$srcMapelId] ?? null;
                    $targetKelasId = $kelasIdMap[$srcKelasId] ?? null;

                    if ($targetGuruId && $targetMapelId && $targetKelasId) {
                        $comboKey = "{$targetGuruId}_{$targetMapelId}_{$targetKelasId}";
                        if (!isset($seenCombos[$comboKey])) {
                            $seenCombos[$comboKey] = true;
                            $assignmentInserts[] = [
                                'school_id' => $currentSchoolId,
                                'guru_id' => $targetGuruId,
                                'mapel_id' => $targetMapelId,
                                'kelas_id' => $targetKelasId,
                                'tahun_ajaran' => '2024/2025',
                                'semester' => 'Ganjil',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $assignmentsCount++;
                        }
                    }
                }

                foreach (array_chunk($assignmentInserts, 500) as $chunk) {
                    DB::table('guru_mapel_kelas')->insert($chunk);
                }
            }

            DB::commit();

            return [
                'school_id' => $currentSchoolId,
                'school_name' => $school->name,
                'gurus_count' => $gurusCount,
                'siswas_count' => $siswasCount,
                'kelases_count' => $kelasesCount,
                'mapels_count' => $mapelsCount,
                'assignments_count' => $assignmentsCount,
                'users_count' => DB::table('users')->count(),
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Helper membuat username bersih dari nama guru (identik dengan web absen)
     */
    public static function generateUsernameFromTeacherName(string $fullName): string
    {
        $nameBeforeComma = explode(',', $fullName)[0];
        $cleanPrefix = preg_replace('/^((dr\.|drs\.|dra\.|prof\.|ir\.|h\.|hj\.|kh\.|k\.h\.|ust\.|ustadz\.)\s*)+/i', '', trim($nameBeforeComma));
        $onlyAlpha = preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanPrefix);
        $words = array_values(array_filter(explode(' ', trim($onlyAlpha)), fn($w) => strlen(trim($w)) > 0));

        if (empty($words)) {
            $fallbackAlpha = preg_replace('/[^a-zA-Z0-9]/', '', $nameBeforeComma);
            $words = !empty($fallbackAlpha) ? [$fallbackAlpha] : ['guru'];
        }

        $selectedWords = array_slice($words, 0, 2);
        $username = strtolower(implode('', $selectedWords));

        return $username ?: 'guru';
    }
}
