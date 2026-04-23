<?php

return [
    'latitude'  => env('ABSENSI_LATITUDE', -5.4677),
    'longitude' => env('ABSENSI_LONGITUDE', 122.6307),
    'radius'    => env('ABSENSI_RADIUS', 200),

    // Jam kantor pegawai Normal (0=Minggu, 1=Senin ... 5=Jumat, 6=Sabtu)
    'jam_kantor' => [
        1 => ['masuk' => '07:30', 'keluar' => '16:00'], // Senin
        2 => ['masuk' => '07:30', 'keluar' => '16:00'], // Selasa
        3 => ['masuk' => '07:30', 'keluar' => '16:00'], // Rabu
        4 => ['masuk' => '07:30', 'keluar' => '16:00'], // Kamis
        5 => ['masuk' => '07:30', 'keluar' => '16:30'], // Jumat
    ],

    // Hari libur mingguan pegawai Normal (Sabtu=6, Minggu=0)
    'hari_libur' => [0, 6],

    'toleransi_menit' => 15,
];
