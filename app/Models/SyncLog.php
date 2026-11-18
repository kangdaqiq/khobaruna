<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'source_url',
        'status',
        'total_gurus',
        'total_siswas',
        'total_kelases',
        'total_mapels',
        'total_users_created',
        'message',
    ];
}

