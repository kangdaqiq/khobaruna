<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'sync_id',
        'name',
        'tahun_ajaran',
        'semester',
        'code',
        'npsn',
        'address',
        'phone',
        'headmaster_name',
        'headmaster_nip',
        'titimangsa_tempat',
        'titimangsa_tanggal',
        'headmaster_signature',
        'school_stamp',
        'logo',
    ];

    protected $casts = [
        'titimangsa_tanggal' => 'date',
    ];

    public function kelases()
    {
        return $this->hasMany(Kelas::class, 'school_id');
    }

    public function gurus()
    {
        return $this->hasMany(Guru::class, 'school_id');
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'school_id');
    }

    public function mapels()
    {
        return $this->hasMany(Mapel::class, 'school_id');
    }
}

