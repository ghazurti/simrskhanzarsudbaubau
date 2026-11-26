<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IzinController;
use App\Http\Controllers\Api\KoreksiAbsensiController;
use App\Http\Controllers\Api\PegawaiController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SkorController;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->name('api.')->group(function () {

    // Logout tetap bisa walau perangkat sudah direset admin
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rute yang wajib cocok dengan perangkat terdaftar (1 HP = 1 pegawai)
    Route::middleware('device.bound')->group(function () {
        // Auth
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Shift (pegawai bisa CRUD shift sendiri)
        Route::apiResource('shifts', ShiftController::class);

        // Absensi
        Route::get('/absensi', [AbsensiController::class, 'index']);
        Route::post('/absensi/check-in', [AbsensiController::class, 'checkIn']);
        Route::post('/absensi/check-out', [AbsensiController::class, 'checkOut']);
        Route::get('/absensi/rekap', [AbsensiController::class, 'rekap']);

        // Skor Kehadiran
        Route::get('/skor', [SkorController::class, 'index']);

        // Izin
        Route::apiResource('izin', IzinController::class)->except(['update', 'edit', 'create']);

        // Koreksi Absensi
        Route::get('/koreksi', [KoreksiAbsensiController::class, 'index']);
        Route::post('/koreksi', [KoreksiAbsensiController::class, 'store']);
        Route::delete('/koreksi/{koreksi}', [KoreksiAbsensiController::class, 'destroy']);

        // Fingerprint
        Route::post('/fingerprint/enroll', [\App\Http\Controllers\Api\FingerprintController::class, 'enroll']);
        Route::post('/fingerprint/attendance', [\App\Http\Controllers\Api\FingerprintController::class, 'attendance']);

        // Admin only
        Route::middleware('admin')->group(function () {
            Route::post('/pegawai/{pegawai}/reset-device', [PegawaiController::class, 'resetDevice']);
            Route::apiResource('pegawai', PegawaiController::class);
        });
    });
});
