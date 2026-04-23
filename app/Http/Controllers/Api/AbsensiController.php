<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Libur;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'user_id' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|in:hadir,terlambat,izin,sakit,alpha',
        ]);

        $user = auth()->user();
        $query = Absensi::with('user', 'shift');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereMonth('tanggal', $request->bulan)
                  ->whereYear('tanggal', $request->tahun);
        }

        if ($request->filled('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('tanggal', 'desc')->get());
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|image|max:3072',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $existing = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json(['message' => 'Anda sudah melakukan check-in hari ini'], 422);
        }

        // Cek jarak
        $locLat = config('attendance.latitude');
        $locLng = config('attendance.longitude');
        $radius = config('attendance.radius');
        $jarak = $this->hitungJarak($request->latitude, $request->longitude, $locLat, $locLng);

        if ($jarak > $radius) {
            return response()->json([
                'message' => 'Anda berada di luar area absen. Jarak: ' . round($jarak) . ' meter'
            ], 422);
        }

        $fotoPath = $request->file('foto')->store('foto-absensi', 'public');

        if ($user->jenis_absensi === 'normal') {
            $hariIni = Carbon::today()->dayOfWeek;
            if (in_array($hariIni, config('attendance.hari_libur'))) {
                return response()->json(['message' => 'Hari ini adalah hari libur. Absensi tidak tersedia.'], 422);
            }
            $liburNasional = Libur::whereDate('tanggal', Carbon::today())->first();
            if ($liburNasional) {
                return response()->json(['message' => 'Hari ini adalah hari libur nasional: ' . $liburNasional->nama_libur . '. Absensi tidak tersedia.'], 422);
            }
        }

        $status = 'hadir';
        if ($user->jenis_absensi === 'normal') {
            $hariIni   = Carbon::today()->dayOfWeek;
            $jamKantor = config('attendance.jam_kantor')[$hariIni] ?? ['masuk' => '07:30'];
            $toleransi = config('attendance.toleransi_menit', 15);
            $jamMasuk  = Carbon::parse($today . ' ' . $jamKantor['masuk']);
            if (Carbon::now()->gt($jamMasuk->addMinutes($toleransi))) {
                $status = 'terlambat';
            }
        } elseif ($request->filled('shift_id')) {
            $shift = Shift::find($request->shift_id);
            if ($shift) {
                $jamMasuk = Carbon::parse($today . ' ' . $shift->jam_masuk);
                if (Carbon::now()->gt($jamMasuk->addMinutes(15))) {
                    $status = 'terlambat';
                }
            }
        }

        $absensi = Absensi::updateOrCreate(
            ['user_id' => $user->id, 'tanggal' => $today],
            [
                'shift_id' => $request->shift_id,
                'check_in' => Carbon::now(),
                'foto_check_in' => $fotoPath,
                'latitude_in' => $request->latitude,
                'longitude_in' => $request->longitude,
                'status' => $status,
            ]
        );

        return response()->json(['message' => 'Check-in berhasil', 'absensi' => $absensi], 201);
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|image|max:3072',
        ]);

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $absensi = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        if (!$absensi || !$absensi->check_in) {
            return response()->json(['message' => 'Anda belum melakukan check-in hari ini'], 422);
        }

        if ($absensi->check_out) {
            return response()->json(['message' => 'Anda sudah melakukan check-out hari ini'], 422);
        }

        $fotoPath = $request->file('foto')->store('foto-absensi', 'public');

        $absensi->update([
            'check_out' => Carbon::now(),
            'foto_check_out' => $fotoPath,
            'latitude_out' => $request->latitude,
            'longitude_out' => $request->longitude,
        ]);

        return response()->json(['message' => 'Check-out berhasil', 'absensi' => $absensi]);
    }

    public function rekap(Request $request)
    {
        $request->validate([
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = auth()->user();
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $userId = $user->isAdmin() ? ($request->get('user_id', $user->id)) : $user->id;

        $absensis = Absensi::where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        $rekap = [
            'hadir' => $absensis->where('status', 'hadir')->count(),
            'terlambat' => $absensis->where('status', 'terlambat')->count(),
            'izin' => $absensis->where('status', 'izin')->count(),
            'sakit' => $absensis->where('status', 'sakit')->count(),
            'alpha' => $absensis->where('status', 'alpha')->count(),
            'total' => $absensis->count(),
        ];

        return response()->json(['rekap' => $rekap, 'detail' => $absensis]);
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371000; // radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $r * $c;
    }
}
