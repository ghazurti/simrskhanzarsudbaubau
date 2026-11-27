<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoreksiAbsensi extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'alasan',
        'status',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
