<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $bulan = Carbon::now()->month;
        $tahun = Carbon::now()->year;

        if ($user->isAdmin()) {
            $data = [
                'total_pegawai' => User::where('role', 'pegawai')->count(),
                'hadir_hari_ini' => Absensi::whereDate('tanggal', $today)->whereIn('status', ['hadir', 'terlambat'])->count(),
                'terlambat_hari_ini' => Absensi::whereDate('tanggal', $today)->where('status', 'terlambat')->count(),
                'psw_hari_ini' => Absensi::with('shift')->whereDate('tanggal', $today)->get()->filter->is_psw->count(),
                'lupa_absen_hari_ini' => Absensi::where('tanggal', '<', $today)->get()->filter->is_lupa_absen->count(),
                'alpha_hari_ini' => User::where('role', 'pegawai')->count() - Absensi::whereDate('tanggal', $today)->count(),
                'izin_hari_ini' => Izin::where('tanggal_mulai', '<=', $today)->where('tanggal_selesai', '>=', $today)->count(),
                'rekap_bulan' => Absensi::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                    ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                'absensi_terkini' => Absensi::with(['user', 'shift'])->whereDate('tanggal', $today)->latest()->take(10)->get(),
                'stats_unit' => User::where('role', '!=', 'admin')
                    ->whereNotNull('unit')
                    ->select('unit')
                    ->distinct()
                    ->get()
                    ->map(function($u) use ($bulan, $tahun) {
                        $usersInUnit = User::where('unit', $u->unit)->pluck('id');
                        $totalDays = Carbon::now()->day;
                        $expectedAbsensi = $usersInUnit->count() * $totalDays;
                        
                        $actualAbsensi = Absensi::whereIn('user_id', $usersInUnit)
                            ->whereMonth('tanggal', $bulan)
                            ->whereYear('tanggal', $tahun)
                            ->whereIn('status', ['hadir', 'terlambat'])
                            ->count();
                            
                        return [
                            'unit' => $u->unit,
                            'presentase' => $expectedAbsensi > 0 ? round(($actualAbsensi / $expectedAbsensi) * 100) : 0,
                            'total' => $actualAbsensi
                        ];
                    })->sortByDesc('presentase')->values(),
            ];
        } else {
            $data = [
                'absensi_hari_ini' => Absensi::where('user_id', $user->id)->whereDate('tanggal', $today)->first(),
                'shift_hari_ini' => Shift::where('user_id', $user->id)->whereDate('tanggal', $today)->first(),
                'rekap_bulan' => [
                    'hadir' => Absensi::where('user_id', $user->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status', 'hadir')->count(),
                    'terlambat' => Absensi::where('user_id', $user->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status', 'terlambat')->count(),
                    'izin' => Absensi::where('user_id', $user->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status', 'izin')->count(),
                    'alpha' => Absensi::where('user_id', $user->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status', 'alpha')->count(),
                ],
                'absensi_bulan' => Absensi::where('user_id', $user->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->orderBy('tanggal', 'desc')->take(5)->get(),
            ];
        }

        return view('dashboard.index', compact('data'));
    }
}
