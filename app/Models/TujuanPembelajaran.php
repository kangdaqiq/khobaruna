<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TujuanPembelajaran extends Model
{
    use HasFactory;

    protected $table = 'tujuan_pembelajarans';

    protected $fillable = [
        'school_id',
        'mapel_id',
        'cp_id',
        'guru_id',
        'tingkat',
        'fase',
        'semester',
        'kode_tp',
        'deskripsi',
        'ringkasan_materi',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'is_active' => 'boolean',
    ];

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function cp()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'cp_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}

