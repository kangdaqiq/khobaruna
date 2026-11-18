<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelases';

    protected $fillable = [
        'sync_id',
        'school_id',
        'nama_kelas',
        'tingkat',
        'jurusan',
        'wali_kelas_id',
        'wali_kelas_nama',
        'jumlah_siswa',
        'kkm',
    ];

    protected $casts = [
        'kkm' => 'integer',
        'jumlah_siswa' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_id');
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function guruMapelKelas()
    {
        return $this->hasMany(GuruMapelKelas::class, 'kelas_id');
    }

    public function guruMapels()
    {
        return $this->belongsToMany(Guru::class, 'guru_mapel_kelas', 'kelas_id', 'guru_id')
            ->withPivot('mapel_id')
            ->withTimestamps();
    }
}

