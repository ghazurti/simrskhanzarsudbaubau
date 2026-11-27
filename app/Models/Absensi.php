<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory, Auditable;

    protected $table = 'absensis';

    protected $fillable = [
        'user_id',
        'shift_id',
        'tanggal',
        'check_in',
        'check_out',
        'foto_check_in',
        'foto_check_out',
        'latitude_in',
        'longitude_in',
        'latitude_out',
        'longitude_out',
        'status',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function getIsPswAttribute()
    {
        if (!$this->check_out) {
            return false;
        }
        $tanggalObj = $this->tanggal instanceof Carbon ? $this->tanggal : Carbon::parse($this->tanggal);
        $tanggalStr = $tanggalObj->toDateString();

        if ($this->shift) {
            // Shift On-Call fleksibel tidak kena PSW
            if ($this->shift->jenis_shift === 'oncall' || str_contains(strtolower((string) $this->shift->keterangan), 'on call') || str_contains(strtolower((string) $this->shift->keterangan), 'on-call')) {
                return false;
            }
            $jamMasuk = Carbon::parse($tanggalStr . ' ' . $this->shift->jam_masuk);
            $jamKeluar = Carbon::parse($tanggalStr . ' ' . $this->shift->jam_keluar);
            if ($jamKeluar->lte($jamMasuk)) {
                $jamKeluar->addDay(); // Shift malam lintas hari (misal 21:00 - 07:00)
            }
            return $this->check_out->lt($jamKeluar);
        }

        // Pegawai normal tanpa shift pada hari kerja Senin-Jumat
        if ($this->user && $this->user->jenis_absensi === 'normal') {
            $hari = $tanggalObj->dayOfWeek;
            $jamKantor = config('attendance.jam_kantor')[$hari] ?? null;
            if ($jamKantor && !empty($jamKantor['keluar'])) {
                $jamKeluar = Carbon::parse($tanggalStr . ' ' . $jamKantor['keluar']);
                return $this->check_out->lt($jamKeluar);
            }
        }

        return false;
    }

    public function getIsLupaAbsenAttribute()
    {
        $tanggalObj = $this->tanggal instanceof Carbon ? $this->tanggal : Carbon::parse($this->tanggal);
        return !$this->check_out && $tanggalObj->isBefore(Carbon::today()) && !in_array($this->status, ['izin', 'sakit', 'alpha']);
    }
}
