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
        $mode = $request->get('mode', 'riwayat');
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $statusHarian = $request->get('status_harian', '');
        $search = trim((string) $request->get('q', ''));
        $userId = $request->get('user_id');
        $unit = $request->get('unit');
        $foto = $request->get('foto');
        $authUser = auth()->user();

        if ($authUser->isKepalaUnit()) {
            $unit = $authUser->unit;
        }

        if ($authUser->isKepalaUnit()) {
            $units = collect([$authUser->unit]);
        } else {
            $units = User::where('role', 'pegawai')->whereNotNull('unit')
                ->distinct()->orderBy('unit')->pluck('unit');
        }

        $pegawais = User::where('role', 'pegawai')
            ->when($unit, fn($q) => $q->where('unit', $unit))
            ->orderBy('name')->get();

        // Mode 2: Semua Pegawai Per Tanggal (termasuk yang belum absen)
        $harianList = collect();
        $harianStats = ['total' => 0, 'sudah' => 0, 'belum' => 0];
        if ($mode === 'harian') {
            $tglStr = Carbon::parse($tanggal)->toDateString();
            $allUsers = User::where('role', 'pegawai')
                ->when($unit, fn($q) => $q->where('unit', $unit))
                ->when($search !== '', fn($q) => $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                }))
                ->with(['absensis' => fn($q) => $q->with('shift')->whereDate('tanggal', $tglStr)])
                ->orderBy('unit')->orderBy('name')
                ->get();

            $mapped = $allUsers->map(function ($u) {
                $absen = $u->absensis->first();
                return (object) [
                    'user' => $u,
                    'absensi' => $absen,
                    'sudah_absen' => $absen !== null,
                ];
            });

            $harianStats = [
                'total' => $mapped->count(),
                'sudah' => $mapped->where('sudah_absen', true)->count(),
                'belum' => $mapped->where('sudah_absen', false)->count(),
            ];

            if ($statusHarian === 'belum') {
                $harianList = $mapped->where('sudah_absen', false)->values();
            } elseif ($statusHarian === 'sudah') {
                $harianList = $mapped->where('sudah_absen', true)->values();
            } else {
                $harianList = $mapped->values();
            }

            $absensis = collect();
            $rekap = collect();

            return view('laporan.index', compact(
                'mode', 'absensis', 'rekap', 'bulan', 'tahun', 'tanggal', 'statusHarian', 'search',
                'pegawais', 'userId', 'unit', 'units', 'foto', 'harianList', 'harianStats'
            ));
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

        $query = Absensi::with(['user', 'shift'])
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

        return view('laporan.index', compact(
            'mode', 'absensis', 'rekap', 'bulan', 'tahun', 'tanggal', 'statusHarian', 'search',
            'pegawais', 'userId', 'unit', 'units', 'foto', 'harianList', 'harianStats'
        ));
    }

    public function koreksiManual(Request $request)
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpha',
            'alasan' => 'required|string|max:255',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        if ($authUser->isKepalaUnit() && $targetUser->unit !== $authUser->unit) {
            abort(403, 'Anda hanya dapat mengoreksi absensi pegawai di unit Anda.');
        }

        $tanggal = Carbon::parse($validated['tanggal'])->toDateString();
        $checkIn = !empty($validated['check_in'])
            ? Carbon::parse($tanggal . ' ' . $validated['check_in'])
            : null;
        $checkOut = !empty($validated['check_out'])
            ? Carbon::parse($tanggal . ' ' . $validated['check_out'])
            : null;

        // Jika shift malam (jam pulang lebih kecil dari jam masuk), anggap hari berikutnya
        if ($checkIn && $checkOut && $checkOut->lte($checkIn)) {
            $checkOut->addDay();
        }

        $catatan = 'Koreksi Kepegawaian (' . $authUser->name . '): ' . trim($validated['alasan']);

        $absensi = Absensi::firstOrNew([
            'user_id' => $targetUser->id,
            'tanggal' => $tanggal,
        ]);

        $absensi->check_in = $checkIn;
        $absensi->check_out = $checkOut;
        $absensi->status = $validated['status'];
        $absensi->keterangan = $catatan;
        $absensi->save();

        return back()->with('success', 'Koreksi absensi ' . $targetUser->name . ' tanggal ' . Carbon::parse($tanggal)->format('d/m/Y') . ' berhasil disimpan.');
    }

    public function koreksiMassal(Request $request)
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'tanggal' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpha',
            'alasan' => 'required|string|max:255',
        ]);

        $tanggal = Carbon::parse($validated['tanggal'])->toDateString();
        $checkIn = !empty($validated['check_in']) ? Carbon::parse($tanggal . ' ' . $validated['check_in']) : null;
        $checkOut = !empty($validated['check_out']) ? Carbon::parse($tanggal . ' ' . $validated['check_out']) : null;
        if ($checkIn && $checkOut && $checkOut->lte($checkIn)) {
            $checkOut->addDay();
        }

        $targetQuery = User::whereIn('id', $validated['user_ids']);
        if ($authUser->isKepalaUnit()) {
            $targetQuery->where('unit', $authUser->unit);
        }
        $targets = $targetQuery->get();

        $catatan = 'Koreksi Massal Kepegawaian (' . $authUser->name . '): ' . trim($validated['alasan']);
        $count = 0;

        foreach ($targets as $u) {
            $absensi = Absensi::firstOrNew([
                'user_id' => $u->id,
                'tanggal' => $tanggal,
            ]);
            $absensi->check_in = $checkIn;
            $absensi->check_out = $checkOut;
            $absensi->status = $validated['status'];
            $absensi->keterangan = $catatan;
            $absensi->save();
            $count++;
        }

        return back()->with('success', "Berhasil mengoreksi absensi {$count} pegawai untuk tanggal " . Carbon::parse($tanggal)->format('d/m/Y') . '.');
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
