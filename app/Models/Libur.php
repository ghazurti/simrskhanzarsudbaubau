<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libur extends Model
{
    protected $fillable = [
        'tanggal',
        'nama_libur',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
        ];
    }
}
