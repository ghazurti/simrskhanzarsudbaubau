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
        $cameraEnabled = (bool) config('attendance.web_camera_enabled', true);

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'shift_id' => 'nullable|exists:shifts,id',
            'foto' => ($cameraEnabled ? 'required' : 'nullable') . '|image|max:3072',
        ]);

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $existing = Absensi::where('user_id', $user->id)->where('tanggal', $today)->first();
        if ($existing && $existing->check_in) {
            return back()->with('error', 'Anda sudah melakukan check-in hari ini.');
        }

        // Cek jadwal shift hari ini lebih dulu untuk deteksi On-Call (bebas radius jarak)
        $shift = null;
        if ($request->filled('shift_id')) {
            $shift = Shift::where('id', $request->shift_id)->where('user_id', $user->id)->first();
        }
        if (!$shift) {
            $shift = Shift::where('user_id', $user->id)->whereDate('tanggal', $today)->first();
        }
        $isOnCall = $user->isItUnit() && $shift && (
            $shift->jenis_shift === 'oncall'
            || str_contains(strtolower((string) $shift->keterangan), 'on call')
            || str_contains(strtolower((string) $shift->keterangan), 'on-call')
        );

        if (!$isOnCall) {
            $jarak = $this->hitungJarak($request->latitude, $request->longitude, config("attendance.latitude"), config("attendance.longitude"));
            if ($jarak > config("attendance.radius")) {
                return back()->with('error', 'Anda berada di luar area absen. Jarak: ' . round($jarak) . ' meter.');
            }
        }

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('foto-absensi', 'public')
            : null;

        if ($user->jenis_absensi === 'shift' && !$user->isItUnit() && !$shift) {
            return back()->with('error', 'Anda belum memiliki jadwal shift hari ini. Silakan tambahkan jadwal shift terlebih dahulu.');
        }

        if (!$shift) {
            $hariIni = Carbon::today()->dayOfWeek;
            if (in_array($hariIni, config('attendance.hari_libur'))) {
                $msg = $user->isItUnit()
                    ? 'Hari ini akhir pekan (Sabtu/Minggu). Jika Anda bertugas On-Call / Shift IT, silakan input jadwal Shift terlebih dahulu di menu Jadwal Shift.'
                    : 'Hari ini adalah hari libur. Absensi tidak tersedia.';
                return back()->with('error', $msg);
            }
            $liburNasional = Libur::whereDate('tanggal', Carbon::today())->first();
            if ($liburNasional) {
                $msg = $user->isItUnit()
                    ? 'Hari ini libur nasional (' . $liburNasional->nama_libur . '). Jika Anda bertugas On-Call IT, silakan input jadwal Shift terlebih dahulu.'
                    : 'Hari ini adalah hari libur nasional: ' . $liburNasional->nama_libur . '. Absensi tidak tersedia.';
                return back()->with('error', $msg);
            }
        }

        $status = 'hadir';
        $toleransi = (int) config('attendance.toleransi_menit', 15);

        if ($shift) {
            $isOnCall = str_contains(strtolower((string) $shift->keterangan), 'on call')
                     || str_contains(strtolower((string) $shift->keterangan), 'on-call');
            if (!$isOnCall) {
                $jamMasuk = Carbon::parse($today . ' ' . $shift->jam_masuk);
                if (Carbon::now()->gt($jamMasuk->addMinutes($toleransi))) {
                    $status = 'terlambat';
                }
            }
        } else {
            $hariIni   = Carbon::today()->dayOfWeek;
            $jamKantor = config('attendance.jam_kantor')[$hariIni] ?? ['masuk' => '07:30'];
            $jamMasuk  = Carbon::parse($today . ' ' . $jamKantor['masuk']);
            if (Carbon::now()->gt($jamMasuk->addMinutes($toleransi))) {
                $status = 'terlambat';
            }
        }

        Absensi::updateOrCreate(
            ['user_id' => $user->id, 'tanggal' => $today],
            [
                'shift_id' => $shift?->id,
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
        $cameraEnabled = (bool) config('attendance.web_camera_enabled', true);

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => ($cameraEnabled ? 'required' : 'nullable') . '|image|max:3072',
        ]);

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $absensi = Absensi::where('user_id', $user->id)->where('tanggal', $today)->first();

        if ((!$absensi || !$absensi->check_in) && Carbon::now()->hour < 12) {
            $yesterday = Carbon::yesterday()->toDateString();
            $absensiKemarin = Absensi::with('shift')
                ->where('user_id', $user->id)
                ->where('tanggal', $yesterday)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->first();
            if ($absensiKemarin && $absensiKemarin->shift) {
                $s = $absensiKemarin->shift;
                $isOvernight = $s->jenis_shift === 'malam'
                    || $s->jam_keluar <= $s->jam_masuk
                    || str_contains(strtolower((string) $s->keterangan), 'on-call')
                    || str_contains(strtolower((string) $s->keterangan), 'on call');
                if ($isOvernight) {
                    $absensi = $absensiKemarin;
                }
            }
            if (false) {
                $absensi = $absensiKemarin;
            }
        }

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

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('foto-absensi', 'public')
            : null;
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
