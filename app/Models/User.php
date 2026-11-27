<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'name',
        'email',
        'nik',
        'nip',
        'no_hp',
        'jabatan',
        'pangkat_gol',
        'unit',
        'jenis_absensi',
        'jenis_absensi_locked',
        'role',
        'device_id',
        'device_name',
        'device_bound_at',
        'foto',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'device_bound_at' => 'datetime',
            'jenis_absensi_locked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }

    public function izins()
    {
        return $this->hasMany(Izin::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isKepalaUnit()
    {
        return $this->role === 'kepala_unit';
    }

    public function isPegawai()
    {
        return $this->role === 'pegawai';
    }

    public function isAdminOrKepalaUnit()
    {
        return in_array($this->role, ['admin', 'kepala_unit']);
    }

    public function isItUnit(): bool
    {
        $u = strtoupper(trim((string) $this->unit));
        if ($u === '') {
            return false;
        }
        if (in_array($u, ['IT', 'SIMRS', 'INSTALASI SIMRS', 'INSTALASI IT', 'TI', 'SIRS', 'PDE'], true)) {
            return true;
        }
        return (bool) preg_match('/\b(IT|SIMRS|SIRS|TEKNOLOGI INFORMASI)\b/i', $u);
    }

    public function canManageShift(): bool
    {
        return $this->jenis_absensi === 'shift' || $this->isItUnit() || $this->isAdmin();
    }
}
