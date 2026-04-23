<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        $query = Shift::with('user')->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $shifts = $query->orderBy('tanggal')->get();
        $users = $user->isAdmin()
            ? \App\Models\User::where('role', 'pegawai')->where('jenis_absensi', 'shift')->orderBy('name')->get()
            : [];

        return view('shift.index', compact('shifts', 'bulan', 'tahun', 'users'));
    }

    public function store(Request $request)
    {
        $isAdmin = auth()->user()->isAdmin();
        
        $request->validate([
            'user_ids' => $isAdmin ? 'required|array' : 'nullable',
            'user_ids.*' => 'exists:users,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis_shift' => 'required|in:pagi,siang,malam',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_keluar' => 'required|date_format:H:i',
            'keterangan' => 'nullable|string',
        ]);

        $userIds = $isAdmin ? $request->user_ids : [auth()->id()];
        $period = \Carbon\CarbonPeriod::create($request->tanggal_mulai, $request->tanggal_selesai);
        
        // Cache data libur untuk efisiensi
        $liburDates = \App\Models\Libur::whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai])
            ->pluck('tanggal')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        $count = 0;
        foreach ($userIds as $userId) {
            foreach ($period as $date) {
                $tanggal = $date->format('Y-m-d');
                
                // Logika Pengecualian Akhir Pekan
                if ($request->has('skip_sabtu') && $date->isSaturday()) continue;
                if ($request->has('skip_minggu') && $date->isSunday()) continue;

                // Logika Pengecualian Libur Nasional
                if ($request->has('skip_libur') && in_array($tanggal, $liburDates)) continue;
                $jamMasuk = $request->jam_masuk;
                $jamKeluar = $request->jam_keluar;

                // Logika Khusus Hari Jumat (Shift Pagi)
                if ($date->isFriday() && $request->jenis_shift == 'pagi') {
                    $jamKeluar = '17:00';
                }

                // Cek duplikasi
                $exists = Shift::where('user_id', $userId)->where('tanggal', $tanggal)->exists();
                if (!$exists) {
                    Shift::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'jenis_shift' => $request->jenis_shift,
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'keterangan' => $request->keterangan,
                    ]);
                    $count++;
                }
            }
        }

        if ($count === 0) {
            return back()->with('info', 'Tidak ada shift baru yang ditambahkan (sudah ada jadwal pada tanggal tersebut).');
        }

        return back()->with('success', "$count jadwal shift berhasil ditambahkan.");
    }

    public function destroy(Shift $shift)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $shift->user_id !== $user->id) {
            abort(403);
        }
        $shift->delete();
        return back()->with('success', 'Shift berhasil dihapus.');
    }
}
