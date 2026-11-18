<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswas';

    protected $fillable = [
        'sync_id',
        'user_id',
        'school_id',
        'kelas_id',
        'nama',
        'nis',
        'nisn',
        'kelas_nama',
        'tgl_lahir',
        'alamat',
        'no_wa',
        'wa_ortu',
        'foto',
        'jenis_kelamin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensis()
    {
        return $this->hasMany(AbsensiSiswa::class);
    }
}

