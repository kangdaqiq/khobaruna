<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapaianPembelajaran extends Model
{
    use HasFactory;

    protected $table = 'capaian_pembelajarans';

    protected $fillable = [
        'school_id',
        'mapel_id',
        'fase',
        'tingkat',
        'elemen',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function tujuanPembelajarans()
    {
        return $this->hasMany(TujuanPembelajaran::class, 'cp_id');
    }
}

