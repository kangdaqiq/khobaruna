<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_WAKA_KURIKULUM = 'waka_kurikulum';
    public const ROLE_WALI_KELAS = 'wali_kelas';
    public const ROLE_GURU = 'guru';
    public const ROLE_SISWA = 'siswa';

    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'password',
        'plain_password',
        'avatar',
        'is_active',
        'sync_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'user_id');
    }

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isWakaKurikulum(): bool
    {
        return $this->role === self::ROLE_WAKA_KURIKULUM;
    }

    public function isWaliKelas(): bool
    {
        return $this->role === self::ROLE_WALI_KELAS;
    }

    public function isGuru(): bool
    {
        return in_array($this->role, [self::ROLE_GURU, self::ROLE_WALI_KELAS, self::ROLE_WAKA_KURIKULUM]);
    }

    public function isSiswa(): bool
    {
        return $this->role === self::ROLE_SISWA;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Super Admin',
            self::ROLE_WAKA_KURIKULUM => 'Waka Kurikulum',
            self::ROLE_WALI_KELAS => 'Wali Kelas',
            self::ROLE_GURU => 'Guru Mapel',
            self::ROLE_SISWA => 'Siswa',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }
}
