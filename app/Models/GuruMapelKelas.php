<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruMapelKelas extends Model
{
    protected $table = 'guru_mapel_kelas';

    protected $fillable = [
        'school_id',
        'guru_id',
        'mapel_id',
        'kelas_id',
        'tahun_ajaran',
        'semester',
        'jumlah_sumatif',
        'bobot_harian',
        'bobot_sas',
        'sync_id',
    ];

    protected $casts = [
        'jumlah_sumatif' => 'integer',
        'bobot_harian' => 'integer',
        'bobot_sas' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }
}

