<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Libur;
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
            $totalPegawai = User::whereIn('role', ['pegawai', 'kepala_unit'])->count();
            $sudahCheckinIds = Absensi::whereDate('tanggal', $today)->whereNotNull('check_in')->pluck('user_id');
            $totalDays = Carbon::now()->day;

            // stats_unit: 2 query gabungan, bukan N+1
            $unitPegawai = User::where('role', '!=', 'admin')
                ->whereNotNull('unit')
                ->selectRaw('unit, COUNT(*) as total')
                ->groupBy('unit')
                ->pluck('total', 'unit');

            $unitHadir = Absensi::join('users', 'absensis.user_id', '=', 'users.id')
                ->whereMonth('absensis.tanggal', $bulan)
                ->whereYear('absensis.tanggal', $tahun)
                ->whereIn('absensis.status', ['hadir', 'terlambat'])
                ->whereNotNull('users.unit')
                ->selectRaw('users.unit, COUNT(*) as total')
                ->groupBy('users.unit')
                ->pluck('total', 'unit');

            $statsUnit = $unitPegawai->map(function ($pegawaiCount, $unit) use ($unitHadir, $totalDays) {
                $hadir = $unitHadir->get($unit, 0);
                $expected = $pegawaiCount * $totalDays;
                return [
                    'unit' => $unit,
                    'presentase' => $expected > 0 ? round(($hadir / $expected) * 100) : 0,
                    'total' => $hadir,
                ];
            })->sortByDesc('presentase')->values();

            $data = [
                'total_pegawai'      => $totalPegawai,
                'hadir_hari_ini'     => Absensi::whereDate('tanggal', $today)->whereIn('status', ['hadir', 'terlambat'])->count(),
                'terlambat_hari_ini' => Absensi::whereDate('tanggal', $today)->where('status', 'terlambat')->count(),
                'psw_hari_ini'       => Absensi::with('shift')
                    ->whereDate('tanggal', $today)
                    ->whereNotNull('check_out')
                    ->whereNotNull('shift_id')
                    ->get()->filter->is_psw->count(),
                'lupa_absen_hari_ini' => Absensi::whereDate('tanggal', '<', $today)
                    ->whereNull('check_out')
                    ->whereNotIn('status', ['izin', 'sakit', 'alpha'])
                    ->count(),
                'alpha_hari_ini'     => User::whereIn('role', ['pegawai', 'kepala_unit'])
                    ->whereNotIn('id', $sudahCheckinIds)
                    ->count(),
                'izin_hari_ini'      => Izin::where('tanggal_mulai', '<=', $today)->where('tanggal_selesai', '>=', $today)->count(),
                'rekap_bulan'        => Absensi::whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                    ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                'absensi_terkini'    => Absensi::with(['user', 'shift'])->whereDate('tanggal', $today)->latest()->take(10)->get(),
                'stats_unit'         => $statsUnit,
            ];
        } else {
            // rekap pegawai: 1 query groupBy, bukan 4 query terpisah
            $rekapRaw = Absensi::where('user_id', $user->id)
                ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $data = [
                'absensi_hari_ini' => Absensi::where('user_id', $user->id)->whereDate('tanggal', $today)->first(),
                'shift_hari_ini'   => Shift::where('user_id', $user->id)->whereDate('tanggal', $today)->first(),
                'libur_hari_ini'   => Libur::whereDate('tanggal', $today)->first(),
                'rekap_bulan'      => [
                    'hadir'     => $rekapRaw->get('hadir', 0),
                    'terlambat' => $rekapRaw->get('terlambat', 0),
                    'izin'      => $rekapRaw->get('izin', 0),
                    'alpha'     => $rekapRaw->get('alpha', 0),
                ],
                'absensi_bulan' => Absensi::where('user_id', $user->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->orderBy('tanggal', 'desc')->take(5)->get(),
            ];
        }

        return view('dashboard.index', compact('data'));
    }
}
