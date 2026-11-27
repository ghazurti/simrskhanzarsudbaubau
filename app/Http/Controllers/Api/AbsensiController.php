<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AuditLog;
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'foto' => 'nullable|image|max:3072',
            'shift_id' => 'nullable|exists:shifts,id',
            'is_mocked' => 'nullable|boolean',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        if ($securityError = $this->validateLocationSecurity($request, 'check_in')) {
            return $securityError;
        }

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $existing = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json(['message' => 'Anda sudah melakukan absen masuk hari ini'], 422);
        }

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('foto-absensi', 'public')
            : null;

        // Cari jadwal shift hari ini (baik dari request shift_id maupun dari tabel shifts hari ini untuk user tersebut)
        $shift = null;
        if ($request->filled('shift_id')) {
            $shift = Shift::where('id', $request->shift_id)->where('user_id', $user->id)->first();
        }
        if (!$shift && ($user->jenis_absensi === 'shift' || $user->isItUnit())) {
            $shift = Shift::where('user_id', $user->id)->whereDate('tanggal', $today)->first();
        }

        // Pegawai shift murni (bukan IT hybrid) wajib memiliki jadwal shift
        if ($user->jenis_absensi === 'shift' && !$user->isItUnit() && !$shift) {
            return response()->json(['message' => 'Anda belum memiliki jadwal shift hari ini. Silakan tambahkan jadwal shift terlebih dahulu.'], 422);
        }

        // Jika tidak ada shift hari ini, terapkan aturan jam kantor Normal (Senin-Jumat 07:30-16:00)
        if (!$shift) {
            $hariIni = Carbon::today()->dayOfWeek;
            if (in_array($hariIni, config('attendance.hari_libur'))) {
                $pesanLibur = $user->isItUnit()
                    ? 'Hari ini adalah akhir pekan (Sabtu/Minggu). Khusus IT/SIMRS yang bertugas On-Call atau Shift, silakan input jadwal Shift terlebih dahulu di menu Jadwal Shift.'
                    : 'Hari ini adalah hari libur. Absensi tidak tersedia.';
                return response()->json(['message' => $pesanLibur], 422);
            }
            $liburNasional = Libur::whereDate('tanggal', Carbon::today())->first();
            if ($liburNasional) {
                $pesanNas = $user->isItUnit()
                    ? 'Hari ini libur nasional (' . $liburNasional->nama_libur . '). Jika Anda piket On-Call IT, silakan input jadwal Shift terlebih dahulu.'
                    : 'Hari ini adalah hari libur nasional: ' . $liburNasional->nama_libur . '. Absensi tidak tersedia.';
                return response()->json(['message' => $pesanNas], 422);
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

        $absensi = Absensi::updateOrCreate(
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

        return response()->json(['message' => 'Absen masuk berhasil', 'absensi' => $absensi], 201);
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'foto' => 'nullable|image|max:3072',
            'is_mocked' => 'nullable|boolean',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        if ($securityError = $this->validateLocationSecurity($request, 'check_out')) {
            return $securityError;
        }

        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $absensi = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        // Dukungan Shift Malam lintas hari: bila hari ini belum ada check-in, cek shift malam kemarin yang belum check-out
        if ((!$absensi || !$absensi->check_in) && Carbon::now()->hour < 12) {
            $yesterday = Carbon::yesterday()->toDateString();
            $absensiKemarin = Absensi::with('shift')
                ->where('user_id', $user->id)
                ->where('tanggal', $yesterday)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->first();
            if ($absensiKemarin && $absensiKemarin->shift && $absensiKemarin->shift->jenis_shift === 'malam') {
                $absensi = $absensiKemarin;
            }
        }

        if (!$absensi || !$absensi->check_in) {
            return response()->json(['message' => 'Anda belum melakukan absen masuk hari ini'], 422);
        }

        if ($absensi->check_out) {
            return response()->json(['message' => 'Anda sudah melakukan absen pulang hari ini'], 422);
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

        return response()->json(['message' => 'Absen pulang berhasil', 'absensi' => $absensi]);
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

    private function validateLocationSecurity(Request $request, string $aksi)
    {
        $user = auth()->user();

        if ($request->boolean('is_mocked')) {
            try {
                AuditLog::create([
                    'user_id' => $user?->id,
                    'aksi' => 'fake_gps_' . $aksi,
                    'model' => 'Absensi',
                    'model_id' => null,
                    'data_lama' => null,
                    'data_baru' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'accuracy' => $request->accuracy,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
            }

            return response()->json([
                'message' => 'Lokasi palsu (Fake GPS) terdeteksi. Percobaan ini telah dicatat oleh sistem.',
            ], 422);
        }

        if ($request->filled('accuracy') && (float) $request->accuracy > 100) {
            return response()->json([
                'message' => 'Akurasi GPS terlalu rendah (' . round((float) $request->accuracy) . ' meter). Pindah ke area lebih terbuka dan coba lagi.',
            ], 422);
        }

        $locLat = config('attendance.latitude');
        $locLng = config('attendance.longitude');
        $radius = config('attendance.radius');
        $jarak = $this->hitungJarak($request->latitude, $request->longitude, $locLat, $locLng);

        if ($jarak > $radius) {
            return response()->json([
                'message' => 'Anda berada di luar area absen. Jarak: ' . round($jarak) . ' meter',
            ], 422);
        }

        return null;
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $r * $c;
    }
}
