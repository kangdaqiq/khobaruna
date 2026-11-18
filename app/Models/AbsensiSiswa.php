<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'absensi_siswas';

    protected $fillable = [
        'school_id',
        'siswa_id',
        'kelas_id',
        'tahun_ajaran',
        'semester',
        'start_date',
        'end_date',
        'masuk',
        'hadir',
        'terlambat',
        'izin',
        'sakit',
        'alpha',
        'bolos',
        'total_hari',
        'persentase',
        'catatan',
        'catatan_wali_kelas',
        'catatan_kokurikuler',
        'kenaikan_kelas',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'masuk' => 'integer',
        'hadir' => 'integer',
        'terlambat' => 'integer',
        'izin' => 'integer',
        'sakit' => 'integer',
        'alpha' => 'integer',
        'bolos' => 'integer',
        'total_hari' => 'integer',
        'persentase' => 'float',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}

