<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KoreksiAbsensi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KoreksiAbsensiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = KoreksiAbsensi::with('user');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $batasJam = config('attendance.koreksi_batas_jam', '22:00');
        $nowStr = Carbon::now()->format('H:i');

        return response()->json([
            'data' => $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->limit(50)->get(),
            'batas_jam' => $batasJam,
            'jam_server' => $nowStr,
            'boleh_ajukan' => $nowStr <= $batasJam,
        ]);
    }

    public function store(Request $request)
    {
        $batasJam = config('attendance.koreksi_batas_jam', '22:00');
        if (Carbon::now()->format('H:i') > $batasJam) {
            return response()->json([
                'message' => "Pengajuan koreksi absensi hanya dibuka sampai pukul {$batasJam} WITA.",
            ], 422);
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

        $koreksi = KoreksiAbsensi::create([
            'user_id' => auth()->id(),
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_keluar' => $request->jam_keluar,
            'alasan' => $request->alasan,
        ]);

        return response()->json([
            'message' => 'Pengajuan koreksi absensi berhasil dikirim.',
            'koreksi' => $koreksi,
        ], 201);
    }

    public function destroy(string $id)
    {
        $koreksi = KoreksiAbsensi::findOrFail($id);
        $user = auth()->user();

        if (!$user->isAdmin() && $koreksi->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        if ($koreksi->status !== 'pending') {
            return response()->json([
                'message' => 'Tidak dapat menghapus pengajuan yang sudah diproses.',
            ], 422);
        }

        $koreksi->delete();

        return response()->json(['message' => 'Pengajuan koreksi dihapus.']);
    }
}
