<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nilai extends Model
{
    protected $table = 'nilais';

    protected $fillable = [
        'school_id',
        'siswa_id',
        'kelas_id',
        'mapel_id',
        'guru_id',
        'sumatif_1',
        'sumatif_2',
        'sumatif_3',
        'sumatif_4',
        'sumatif_5',
        'sumatif_6',
        'rata_sumatif_harian',
        'nilai_tugas',
        'nilai_uts',
        'nilai_uas',
        'nilai_akhir',
        'capaian_kompetensi',
        'tahun_ajaran',
        'semester',
    ];

    protected $casts = [
        'sumatif_1' => 'float',
        'sumatif_2' => 'float',
        'sumatif_3' => 'float',
        'sumatif_4' => 'float',
        'sumatif_5' => 'float',
        'sumatif_6' => 'float',
        'rata_sumatif_harian' => 'float',
        'nilai_tugas' => 'float',
        'nilai_uts' => 'float',
        'nilai_uas' => 'float',
        'nilai_akhir' => 'float',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Alias STS (Sumatif Tengah Semester) untuk nilai_uts
     */
    public function getNilaiStsAttribute(): ?float
    {
        return $this->nilai_uts;
    }

    public function setNilaiStsAttribute($value): void
    {
        $this->attributes['nilai_uts'] = $value !== null && $value !== '' ? (float) $value : null;
    }
}

