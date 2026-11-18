<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $table = 'mapels';

    protected $fillable = [
        'sync_id',
        'school_id',
        'nama_mapel',
        'kode_mapel',
        'kelompok',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function guruMapelKelas()
    {
        return $this->hasMany(GuruMapelKelas::class, 'mapel_id');
    }

    public function gurus()
    {
        return $this->belongsToMany(Guru::class, 'guru_mapel_kelas', 'mapel_id', 'guru_id')
            ->withPivot('kelas_id')
            ->withTimestamps();
    }
}

