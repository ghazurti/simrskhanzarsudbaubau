<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Libur;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    // Koordinat Rumah/Kantor akan diambil dari config/attendance.php

    public function index(Request $request)
    {
        $user = auth()->user();
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        $query = Absensi::with(['user', 'shift'])->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $absensis       = $query->orderBy('tanggal', 'desc')->paginate(20);
        $shiftHariIni   = Shift::where('user_id', $user->id)->whereDate('tanggal', Carbon::today())->first();
        $absensiHariIni = Absensi::where('user_id', $user->id)->whereDate('tanggal', Carbon::today())->first();
        $liburHariIni   = Libur::whereDate('tanggal', Carbon::today())->first();

        return view('absensi.index', compact('absensis', 'bulan', 'tahun', 'shiftHariIni', 'absensiHariIni', 'liburHariIni'));
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|image|max:3072',
        ]);

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $existing = Absensi::where('user_id', $user->id)->where('tanggal', $today)->first();
        if ($existing && $existing->check_in) {
            return back()->with('error', 'Anda sudah melakukan check-in hari ini.');
        }

        $jarak = $this->hitungJarak($request->latitude, $request->longitude, config("attendance.latitude"), config("attendance.longitude"));
        if ($jarak > config("attendance.radius")) {
            return back()->with('error', 'Anda berada di luar area absen. Jarak: ' . round($jarak) . ' meter.');
        }

        $fotoPath = $request->file('foto')->store('foto-absensi', 'public');

        if ($user->jenis_absensi === 'normal') {
            $hariIni = Carbon::today()->dayOfWeek;
            if (in_array($hariIni, config('attendance.hari_libur'))) {
                return back()->with('error', 'Hari ini adalah hari libur. Absensi tidak tersedia.');
            }
            $liburNasional = Libur::whereDate('tanggal', Carbon::today())->first();
            if ($liburNasional) {
                return back()->with('error', 'Hari ini adalah hari libur nasional: ' . $liburNasional->nama_libur . '. Absensi tidak tersedia.');
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

        Absensi::updateOrCreate(
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

        return back()->with('success', 'Check-in berhasil! Selamat bekerja.');
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

        $absensi = Absensi::where('user_id', $user->id)->where('tanggal', $today)->first();

        if (!$absensi || !$absensi->check_in) {
            return back()->with('error', 'Anda belum melakukan check-in hari ini.');
        }
        if ($absensi->check_out) {
            return back()->with('error', 'Anda sudah melakukan check-out hari ini.');
        }

        $jarak = $this->hitungJarak($request->latitude, $request->longitude, config("attendance.latitude"), config("attendance.longitude"));
        if ($jarak > config("attendance.radius")) {
            return back()->with('error', 'Anda berada di luar area absen. Jarak: ' . round($jarak) . ' meter.');
        }

        $fotoPath = $request->file('foto')->store('foto-absensi', 'public');
        $absensi->update([
            'check_out' => Carbon::now(),
            'foto_check_out' => $fotoPath,
            'latitude_out' => $request->latitude,
            'longitude_out' => $request->longitude,
        ]);

        return back()->with('success', 'Check-out berhasil! Terima kasih atas kerja keras Anda.');
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
