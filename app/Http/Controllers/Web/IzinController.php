<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IzinController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Izin::with('user');
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isKepalaUnit()) {
            $query->whereHas('user', fn($q) => $q->where('unit', $user->unit));
        }

        $izins = $query->orderBy('tanggal_mulai', 'desc')->paginate(15);

        $pegawais = collect();
        if ($user->isAdmin()) {
            $pegawaiQuery = User::where('role', 'pegawai')->orderBy('name');
            if ($user->isKepalaUnit()) {
                $pegawaiQuery->where('unit', $user->unit);
            }
            $pegawais = $pegawaiQuery->get();
        }

        return view('izin.index', compact('izins', 'pegawais'));
    }

    public function store(Request $request)
    {
        $isAdmin = auth()->user()->isAdmin();

        $rules = [
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|in:izin,sakit,cuti',
            'keterangan' => 'required|string',
            'file_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
        if ($isAdmin) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        $data = $request->only(['tanggal_mulai', 'tanggal_selesai', 'jenis', 'keterangan']);
        $data['user_id'] = $isAdmin ? $request->user_id : auth()->id();
        if ($isAdmin) {
            $data['status'] = 'approved';
        }

        if ($request->hasFile('file_pendukung')) {
            $data['file_pendukung'] = $request->file('file_pendukung')->store('izin-files', 'public');
        }

        Izin::create($data);
        return back()->with('success', $isAdmin ? 'Data cuti/izin pegawai beserta berkas berhasil disimpan.' : 'Pengajuan izin berhasil disimpan.');
    }

    public function uploadFile(Request $request, Izin $izin)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $request->validate([
            'file_pendukung' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($izin->file_pendukung && Storage::disk('public')->exists($izin->file_pendukung)) {
            Storage::disk('public')->delete($izin->file_pendukung);
        }

        $path = $request->file('file_pendukung')->store('izin-files', 'public');
        $izin->update(['file_pendukung' => $path]);

        return back()->with('success', 'Berkas cuti/izin berhasil diunggah.');
    }

    public function destroy(Izin $izin)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $izin->user_id !== $user->id) abort(403);
        
        // Cegah penghapusan jika sudah disetujui/ditolak (untuk pegawai)
        if (!$user->isAdmin() && $izin->status !== 'pending') {
            return back()->with('error', 'Tidak dapat menghapus pengajuan yang sudah diproses.');
        }

        $izin->delete();
        return back()->with('success', 'Data izin berhasil dihapus.');
    }

    public function approve(Izin $izin)
    {
        $izin->update(['status' => 'approved']);
        return back()->with('success', 'Pengajuan izin berhasil disetujui.');
    }

    public function reject(Request $request, Izin $izin)
    {
        $request->validate(['catatan_admin' => 'nullable|string|max:255']);
        $izin->update([
            'status' => 'rejected',
            'catatan_admin' => $request->catatan_admin
        ]);
        return back()->with('success', 'Pengajuan izin berhasil ditolak.');
    }
}
