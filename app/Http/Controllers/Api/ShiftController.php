<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Shift::with('user');

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

        return response()->json($query->orderBy('tanggal')->get());
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageShift()) {
            return response()->json([
                'message' => 'Anda terdaftar sebagai pegawai absensi normal. Fitur input shift mandiri hanya untuk pegawai Shift dan unit IT/SIMRS.'
            ], 403);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'jenis_shift' => 'required|in:pagi,siang,malam',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_keluar' => 'required|date_format:H:i',
            'keterangan' => 'nullable|string',
        ]);

        $shift = Shift::updateOrCreate(
            [
                'user_id' => $user->id,
                'tanggal' => $request->tanggal,
            ],
            [
                'jenis_shift' => $request->jenis_shift,
                'jam_masuk' => $request->jam_masuk,
                'jam_keluar' => $request->jam_keluar,
                'keterangan' => $request->keterangan,
            ]
        );

        return response()->json(['message' => 'Shift berhasil disimpan', 'shift' => $shift], 201);
    }

    public function show(string $id)
    {
        $shift = Shift::with('user', 'absensi')->findOrFail($id);
        $this->authorizeAccess($shift);

        return response()->json($shift);
    }

    public function update(Request $request, string $id)
    {
        $shift = Shift::findOrFail($id);
        $this->authorizeAccess($shift);

        $request->validate([
            'tanggal' => 'sometimes|date',
            'jenis_shift' => 'sometimes|in:pagi,siang,malam',
            'jam_masuk' => 'sometimes|date_format:H:i',
            'jam_keluar' => 'sometimes|date_format:H:i',
            'keterangan' => 'nullable|string',
        ]);

        $shift->update($request->only(['tanggal', 'jenis_shift', 'jam_masuk', 'jam_keluar', 'keterangan']));

        return response()->json(['message' => 'Shift berhasil diupdate', 'shift' => $shift]);
    }

    public function destroy(string $id)
    {
        $shift = Shift::findOrFail($id);
        $this->authorizeAccess($shift);

        $shift->delete();

        return response()->json(['message' => 'Shift berhasil dihapus']);
    }

    private function authorizeAccess(Shift $shift)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $shift->user_id !== $user->id) {
            abort(403, 'Tidak diizinkan');
        }
    }
}
