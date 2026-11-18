<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\School;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Master School
        $school = School::create([
            'name' => 'SMK Mandiri Berkarya',
            'code' => 'SMK-MB',
            'npsn' => '20198765',
            'address' => 'Jl. Pendidikan No. 45, Kota Pembelajaran',
            'phone' => '021-888999',
            'headmaster_name' => 'Drs. H. Mulyadi, M.Pd.',
            'headmaster_nip' => '197005121995121001',
        ]);

        // 2. Kelas
        $kelas1 = Kelas::create([
            'school_id' => $school->id,
            'nama_kelas' => 'XII RPL 1',
            'tingkat' => '12',
            'jurusan' => 'Rekayasa Perangkat Lunak',
            'jumlah_siswa' => 32,
        ]);

        $kelas2 = Kelas::create([
            'school_id' => $school->id,
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
            'jurusan' => 'Rekayasa Perangkat Lunak',
            'jumlah_siswa' => 30,
        ]);

        $kelas3 = Kelas::create([
            'school_id' => $school->id,
            'nama_kelas' => 'X PPLG 1',
            'tingkat' => '10',
            'jurusan' => 'Pengembangan Perangkat Lunak dan Gim',
            'jumlah_siswa' => 36,
        ]);

        // 3. Mapel
        $mapel1 = Mapel::create(['school_id' => $school->id, 'nama_mapel' => 'Pendidikan Agama dan Budi Pekerti', 'kode_mapel' => 'PABP', 'kelompok' => 'Umum']);
        $mapel2 = Mapel::create(['school_id' => $school->id, 'nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BIND', 'kelompok' => 'Umum']);
        $mapel3 = Mapel::create(['school_id' => $school->id, 'nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'kelompok' => 'Umum']);
        $mapel4 = Mapel::create(['school_id' => $school->id, 'nama_mapel' => 'Bahasa Inggris', 'kode_mapel' => 'BING', 'kelompok' => 'Umum']);
        $mapel5 = Mapel::create(['school_id' => $school->id, 'nama_mapel' => 'Pemrograman Web & Perangkat Bergerak', 'kode_mapel' => 'PWPB', 'kelompok' => 'Kejuruan']);

        // 4. User 1: Administrator
        $admin = User::create([
            'name' => 'Administrator E-Rapor',
            'username' => 'admin',
            'email' => 'admin@erapor.local',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('admin123'),
            'is_active' => true,
        ]);

        // 5. User 2: Waka Kurikulum
        $wakaUser = User::create([
            'name' => 'Bambang Sutedjo, M.Pd.',
            'username' => 'kurikulum',
            'email' => 'kurikulum@erapor.local',
            'role' => User::ROLE_WAKA_KURIKULUM,
            'password' => Hash::make('guru123'),
            'is_active' => true,
        ]);
        $wakaGuru = Guru::create([
            'user_id' => $wakaUser->id,
            'school_id' => $school->id,
            'nama' => 'Bambang Sutedjo, M.Pd.',
            'nip' => '198203152008011005',
            'no_wa' => '081234567891',
            'email' => 'kurikulum@erapor.local',
            'is_wali_kelas' => false,
        ]);

        // 6. User 3: Wali Kelas
        $waliUser = User::create([
            'name' => 'Siti Nurhaliza, S.Kom.',
            'username' => 'walikelas',
            'email' => 'walikelas@erapor.local',
            'role' => User::ROLE_WALI_KELAS,
            'password' => Hash::make('guru123'),
            'is_active' => true,
        ]);
        $waliGuru = Guru::create([
            'user_id' => $waliUser->id,
            'school_id' => $school->id,
            'nama' => 'Siti Nurhaliza, S.Kom.',
            'nip' => '198811202014022003',
            'no_wa' => '081234567892',
            'email' => 'walikelas@erapor.local',
            'is_wali_kelas' => true,
            'kelas_id' => $kelas1->id,
        ]);
        $kelas1->update([
            'wali_kelas_id' => $waliGuru->id,
            'wali_kelas_nama' => $waliGuru->nama,
        ]);

        // 7. User 4: Guru Mapel
        $guruUser = User::create([
            'name' => 'Ahmad Fauzi, S.Pd.',
            'username' => 'guru',
            'email' => 'guru@erapor.local',
            'role' => User::ROLE_GURU,
            'password' => Hash::make('guru123'),
            'is_active' => true,
        ]);
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'school_id' => $school->id,
            'nama' => 'Ahmad Fauzi, S.Pd.',
            'nip' => '199204102019031008',
            'no_wa' => '081234567893',
            'email' => 'guru@erapor.local',
            'is_wali_kelas' => false,
        ]);

        // 8. User 5: Siswa
        $siswaUser = User::create([
            'name' => 'Muhammad Rizky Pratama',
            'username' => '1001',
            'email' => '1001@siswa.local',
            'role' => User::ROLE_SISWA,
            'password' => Hash::make('siswa123'),
            'is_active' => true,
        ]);
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'school_id' => $school->id,
            'kelas_id' => $kelas1->id,
            'nama' => 'Muhammad Rizky Pratama',
            'nis' => '1001',
            'nisn' => '0061234567',
            'kelas_nama' => 'XII RPL 1',
            'tgl_lahir' => '2006-05-14',
            'alamat' => 'Jl. Merpati Putih No. 12',
            'no_wa' => '089876543210',
            'wa_ortu' => '081299887766',
            'jenis_kelamin' => 'Laki-laki',
        ]);

        // Tambah sample siswa kedua
        $siswaUser2 = User::create([
            'name' => 'Annisa Rahmawati',
            'username' => '1002',
            'email' => '1002@siswa.local',
            'role' => User::ROLE_SISWA,
            'password' => Hash::make('siswa123'),
            'is_active' => true,
        ]);
        Siswa::create([
            'user_id' => $siswaUser2->id,
            'school_id' => $school->id,
            'kelas_id' => $kelas1->id,
            'nama' => 'Annisa Rahmawati',
            'nis' => '1002',
            'nisn' => '0067654321',
            'kelas_nama' => 'XII RPL 1',
            'tgl_lahir' => '2006-08-22',
            'alamat' => 'Jl. Kenanga Indah No. 5',
            'no_wa' => '089876543211',
            'wa_ortu' => '081299887755',
            'jenis_kelamin' => 'Perempuan',
        ]);
    }
}
