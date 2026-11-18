<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'gurus';

    protected $fillable = [
        'sync_id',
        'user_id',
        'school_id',
        'nama',
        'nip',
        'tgl_lahir',
        'no_wa',
        'email',
        'foto',
        'signature',
        'is_wali_kelas',
        'kelas_id',
    ];

    protected $casts = [
        'is_wali_kelas' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function kelasWali()
    {
        return $this->hasOne(Kelas::class, 'wali_kelas_id');
    }

    public function guruMapelKelas()
    {
        return $this->hasMany(GuruMapelKelas::class, 'guru_id');
    }

    public function assignments()
    {
        return $this->belongsToMany(Mapel::class, 'guru_mapel_kelas', 'guru_id', 'mapel_id')
            ->withPivot('kelas_id', 'tahun_ajaran', 'semester')
            ->withTimestamps();
    }
}

