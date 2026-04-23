<?php

use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\AbsensiController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DepartmentController;
use App\Http\Controllers\Web\IzinController;
use App\Http\Controllers\Web\LaporanController;
use App\Http\Controllers\Web\SkorController;
use App\Http\Controllers\Web\PegawaiController;
use App\Http\Controllers\Web\ShiftController;
use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard atau login
Route::get('/', fn() => redirect()->route('dashboard'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil
    Route::get('/profil', [AuthController::class, 'profil'])->name('profil');
    Route::post('/profil', [AuthController::class, 'updateProfil'])->name('profil.update');
    Route::get('/profil/ganti-password', [AuthController::class, 'gantiPassword'])->name('profil.ganti-password');
    Route::post('/profil/ganti-password', [AuthController::class, 'updatePassword'])->name('profil.update-password');

    // Absensi
    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
    Route::post('/absensi/check-in', [AbsensiController::class, 'checkIn'])->name('absensi.checkin');
    Route::post('/absensi/check-out', [AbsensiController::class, 'checkOut'])->name('absensi.checkout');

    // Shift
    Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
    Route::post('/shift', [ShiftController::class, 'store'])->name('shift.store');
    Route::delete('/shift/{shift}', [ShiftController::class, 'destroy'])->name('shift.destroy');

    // Izin
    Route::get('/izin', [IzinController::class, 'index'])->name('izin.index');
    Route::post('/izin', [IzinController::class, 'store'])->name('izin.store');
    Route::delete('/izin/{izin}', [IzinController::class, 'destroy'])->name('izin.destroy');

    // Admin only
    Route::middleware('admin')->group(function () {
        // Kelola Pegawai
        Route::get('/pegawai/template', [PegawaiController::class, 'importTemplate'])->name('pegawai.template');
        Route::post('/pegawai/import', [PegawaiController::class, 'import'])->name('pegawai.import');
        Route::resource('/pegawai', PegawaiController::class);

        // Kelola Departemen
        Route::get('/departemen/template', [DepartmentController::class, 'importTemplate'])->name('departemen.template');
        Route::post('/departemen/import', [DepartmentController::class, 'import'])->name('departemen.import');
        Route::resource('/departemen', DepartmentController::class)->parameters([
            'departemen' => 'departemen'
        ]);

        // Laporan & Rekapitulasi
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
        Route::get('/laporan/rekap', [LaporanController::class, 'rekap'])->name('laporan.rekap');
        Route::get('/laporan/rekap/export', [LaporanController::class, 'exportRekap'])->name('laporan.export_rekap');


        // Persetujuan Izin/Cuti
        Route::post('/izin/{izin}/approve', [IzinController::class, 'approve'])->name('izin.approve');
        Route::post('/izin/{izin}/reject', [IzinController::class, 'reject'])->name('izin.reject');

        // Persetujuan Lembur
        Route::post('/lembur/{lembur}/approve', [\App\Http\Controllers\Web\LemburController::class, 'approve'])->name('lembur.approve');
        Route::post('/lembur/{lembur}/reject', [\App\Http\Controllers\Web\LemburController::class, 'reject'])->name('lembur.reject');

        // Manajemen Hari Libur Nasional
        Route::resource('/libur', \App\Http\Controllers\Web\LiburController::class)->only(['index', 'store', 'destroy']);

        // Persetujuan Koreksi Absensi
        Route::post('/koreksi/{koreksi}/approve', [\App\Http\Controllers\Web\KoreksiAbsensiController::class, 'approve'])->name('koreksi.approve');
        Route::post('/koreksi/{koreksi}/reject', [\App\Http\Controllers\Web\KoreksiAbsensiController::class, 'reject'])->name('koreksi.reject');
        // Audit Log
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.logs');
    });

    // Skor Kehadiran (Pegawai & Admin)
    Route::get('/laporan/skor', [SkorController::class, 'index'])->name('skor.index');
    Route::get('/laporan/skor/cetak', [SkorController::class, 'cetak'])->name('skor.cetak');
    Route::get('/laporan/skor/export', [SkorController::class, 'exportExcel'])->name('skor.export');

    // Lembur (Pegawai & Admin)
    Route::resource('/lembur', \App\Http\Controllers\Web\LemburController::class)->only(['index', 'store', 'destroy']);

    // Koreksi Absensi (Pegawai & Admin)
    Route::resource('/koreksi', \App\Http\Controllers\Web\KoreksiAbsensiController::class)->only(['index', 'store', 'destroy']);
    
    // Tukar Shift
    Route::get('/tukar-shift', [\App\Http\Controllers\Web\TukarShiftController::class, 'index'])->name('tukar_shift.index');
    Route::post('/tukar-shift', [\App\Http\Controllers\Web\TukarShiftController::class, 'store'])->name('tukar_shift.store');
    Route::post('/tukar-shift/{tukarShift}/confirm', [\App\Http\Controllers\Web\TukarShiftController::class, 'confirm'])->name('tukar_shift.confirm');
    Route::post('/tukar-shift/{tukarShift}/approve', [\App\Http\Controllers\Web\TukarShiftController::class, 'approve'])->name('tukar_shift.approve');
    Route::get('/tukar-shift/peer-shifts/{user}', [\App\Http\Controllers\Web\TukarShiftController::class, 'getPeerShifts'])->name('tukar_shift.peer_shifts');

    // Fingerprint (Web Access)
    Route::post('/fingerprint/enroll', [\App\Http\Controllers\Api\FingerprintController::class, 'enroll'])->name('fingerprint.enroll');
    Route::post('/fingerprint/attendance', [\App\Http\Controllers\Api\FingerprintController::class, 'attendance'])->name('fingerprint.attendance');
});
