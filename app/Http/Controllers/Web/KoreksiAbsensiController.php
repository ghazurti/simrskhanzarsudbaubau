<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\KoreksiAbsensi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KoreksiAbsensiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = KoreksiAbsensi::with('user');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $koreksis = $query->orderBy('tanggal', 'desc')->paginate(15);
        return view('koreksi.index', compact('koreksis'));
    }

    public function store(Request $request)
    {
        $batasJam = config('attendance.koreksi_batas_jam', '22:00');
        if (Carbon::now()->format('H:i') > $batasJam) {
            return back()
                ->with('error', "Pengajuan koreksi absensi hanya dibuka sampai pukul {$batasJam} WITA.")
                ->withInput();
        }

        $request->validate([
            'tanggal' => 'required|date|before_or_equal:today',
            'jam_masuk' => 'nullable|date_format:H:i|required_without:jam_keluar',
            'jam_keluar' => 'nullable|date_format:H:i|required_without:jam_masuk',
            'alasan' => 'required|string|max:500',
        ], [
            'jam_masuk.required_without' => 'Isi minimal salah satu antara Jam Masuk atau Jam Keluar.',
            'jam_keluar.required_without' => 'Isi minimal salah satu antara Jam Masuk atau Jam Keluar.',
        ]);

        KoreksiAbsensi::create([
            'user_id' => auth()->id(),
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_keluar' => $request->jam_keluar,
            'alasan' => $request->alasan,
        ]);

        return back()->with('success', 'Pengajuan koreksi berhasil dikirim.');
    }

    public function approve(Request $request, KoreksiAbsensi $koreksi)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $koreksi->update(['status' => 'approved', 'catatan_admin' => $request->catatan_admin]);

        // Update atau buat data Absensi asli tanpa menghapus jam yang tidak dikoreksi
        $tanggalStr = Carbon::parse($koreksi->tanggal)->format('Y-m-d');
        $absensi = Absensi::firstOrNew([
            'user_id' => $koreksi->user_id,
            'tanggal' => $tanggalStr,
        ]);

        if ($koreksi->jam_masuk) {
            $absensi->check_in = Carbon::parse($tanggalStr . ' ' . $koreksi->jam_masuk);
        }
        if ($koreksi->jam_keluar) {
            $absensi->check_out = Carbon::parse($tanggalStr . ' ' . $koreksi->jam_keluar);
            $absensi->is_lupa_absen = false;
        }
        $absensi->status = 'hadir';
        $absensi->save();

        return back()->with('success', 'Koreksi disetujui dan data absensi telah diperbarui.');
    }

    public function reject(Request $request, KoreksiAbsensi $koreksi)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $request->validate(['catatan_admin' => 'required']);

        $koreksi->update([
            'status' => 'rejected',
            'catatan_admin' => $request->catatan_admin
        ]);

        return back()->with('success', 'Pengajuan koreksi ditolak.');
    }

    public function destroy(KoreksiAbsensi $koreksi)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $koreksi->user_id !== $user->id) {
            abort(403);
        }
        if ($koreksi->status !== 'pending') {
            return back()->with('error', 'Tidak dapat menghapus pengajuan yang sudah diproses.');
        }
        $koreksi->delete();
        return back()->with('success', 'Pengajuan koreksi dihapus.');
    }
}
