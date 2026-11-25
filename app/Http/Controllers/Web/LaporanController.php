<?php

namespace App\Http\Controllers\Web;

use App\Exports\LaporanAbsensiExport;
use App\Exports\RekapAbsensiExport;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $userId = $request->get('user_id');
        $unit = $request->get('unit');
        $foto = $request->get('foto');
        $authUser = auth()->user();

        if ($authUser->isKepalaUnit()) {
            $unit = $authUser->unit;
        }

        $applyFotoFilter = function ($q) use ($foto) {
            if ($foto === 'ada') {
                $q->where(function ($sub) {
                    $sub->whereNotNull('foto_check_in')->orWhereNotNull('foto_check_out');
                });
            } elseif ($foto === 'tidak') {
                $q->whereNull('foto_check_in')->whereNull('foto_check_out');
            }
        };

        $query = Absensi::with('user')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        if ($userId) $query->where('user_id', $userId);

        if ($unit) {
            $query->whereHas('user', fn($q) => $q->where('unit', $unit));
        }

        $applyFotoFilter($query);

        $absensis = $query->orderBy('tanggal', 'desc')->paginate(20)->withQueryString();

        $rekapQuery = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($unit, fn($q) => $q->whereHas('user', fn($q2) => $q2->where('unit', $unit)));

        $applyFotoFilter($rekapQuery);

        $rekap = $rekapQuery->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pegawais = User::where('role', 'pegawai')
            ->when($unit, fn($q) => $q->where('unit', $unit))
            ->orderBy('name')->get();

        if ($authUser->isKepalaUnit()) {
            $units = collect([$authUser->unit]);
        } else {
            $units = User::where('role', 'pegawai')->whereNotNull('unit')
                ->distinct()->orderBy('unit')->pluck('unit');
        }

        return view('laporan.index', compact('absensis', 'rekap', 'bulan', 'tahun', 'pegawais', 'userId', 'unit', 'units', 'foto'));
    }

    public function export(Request $request)
    {
        $bulan    = (int) $request->get('bulan', Carbon::now()->month);
        $tahun    = (int) $request->get('tahun', Carbon::now()->year);
        $userId   = $request->get('user_id') ? (int) $request->get('user_id') : null;
        $unit     = $request->get('unit');
        $authUser = auth()->user();

        if ($authUser->isKepalaUnit()) {
            $unit = $authUser->unit;
        }

        return Excel::download(
            new LaporanAbsensiExport($bulan, $tahun, $userId, $unit),
            "laporan-absensi-{$bulan}-{$tahun}.xlsx"
        );
    }
    public function rekap(Request $request)
    {
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $unit = $request->get('unit');
        $authUser = auth()->user();

        if ($authUser->isKepalaUnit()) {
            $unit = $authUser->unit;
        }

        $users = User::where('role', 'pegawai')
            ->when($unit, fn($q) => $q->where('unit', $unit))
            ->with(['absensis' => function ($q) use ($bulan, $tahun) {
                $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->orderBy('unit')->orderBy('name')
            ->get();

        if ($authUser->isKepalaUnit()) {
            $units = collect([$authUser->unit]);
        } else {
            $units = User::where('role', 'pegawai')->whereNotNull('unit')
                ->distinct()->orderBy('unit')->pluck('unit');
        }

        return view('laporan.rekap', compact('users', 'bulan', 'tahun', 'unit', 'units'));
    }

    public function exportRekap(Request $request)
    {
        $bulan    = (int) $request->get('bulan', Carbon::now()->month);
        $tahun    = (int) $request->get('tahun', Carbon::now()->year);
        $unit     = $request->get('unit');
        $authUser = auth()->user();

        if ($authUser->isKepalaUnit()) {
            $unit = $authUser->unit;
        }

        return Excel::download(
            new RekapAbsensiExport($bulan, $tahun, $unit),
            "rekapitulasi-absensi-{$bulan}-{$tahun}.xlsx"
        );
    }
}
