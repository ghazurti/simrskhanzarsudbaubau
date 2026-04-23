<?php

namespace App\Http\Controllers\Web;

use App\Exports\TemplatePegawaiExport;
use App\Http\Controllers\Controller;
use App\Imports\PegawaiImport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['pegawai', 'kepala_unit']);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%')
                  ->orWhere('unit', 'like', '%' . $request->search . '%');
            });
        }
        $pegawais = $query->orderBy('name')->paginate(15)->withQueryString();
        return view('pegawai.index', compact('pegawais'));
    }

    public function create()
    {
        try {
            $departments = \App\Models\Department::orderBy('nama')->get();
        } catch (\Exception $e) {
            $departments = collect();
        }
        return view('pegawai.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'nik' => 'required|string|unique:users',
            'nip' => 'nullable|string|unique:users',
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'pangkat_gol' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:100',
            'jenis_absensi' => 'required|in:normal,shift',
            'role' => 'required|in:pegawai,kepala_unit,admin',
            'password' => 'nullable|min:6|confirmed',
        ]);

        try {
            // Smart Defaults
            $email = $request->email ?: $request->nik . '@rsud-baubau.go.id';
            $password = $request->password ?: $request->nik;

            User::create([
                'name' => $request->name,
                'email' => $email,
                'nik' => $request->nik,
                'nip' => $request->nip,
                'no_hp' => $request->no_hp,
                'jabatan' => $request->jabatan,
                'pangkat_gol' => $request->pangkat_gol,
                'unit' => $request->unit,
                'jenis_absensi' => $request->jenis_absensi,
                'role' => $request->role,
                'password' => Hash::make($password),
            ]);

            return redirect()->route('pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    public function importTemplate()
    {
        return Excel::download(new TemplatePegawaiExport(), 'template_import_pegawai.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        $import = new PegawaiImport();
        Excel::import($import, $request->file('file'));

        $msg = "{$import->imported} pegawai berhasil diimpor.";
        if ($import->errors > 0) {
            $msg .= " Gagal: {$import->errors} baris.";
        }

        return redirect()->route('pegawai.index')->with('success', $msg);
    }

    public function edit(User $pegawai)
    {
        try {
            $departments = \App\Models\Department::orderBy('nama')->get();
        } catch (\Exception $e) {
            $departments = collect();
        }
        return view('pegawai.edit', compact('pegawai', 'departments'));
    }

    public function update(Request $request, User $pegawai)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $pegawai->id,
            'nik' => 'required|string|unique:users,nik,' . $pegawai->id,
            'nip' => 'nullable|string|unique:users,nip,' . $pegawai->id,
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'pangkat_gol' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:100',
            'jenis_absensi' => 'required|in:normal,shift',
            'role' => 'required|in:pegawai,kepala_unit,admin',
        ]);

        $data = $request->only(['name', 'email', 'nik', 'nip', 'no_hp', 'jabatan', 'pangkat_gol', 'unit', 'jenis_absensi', 'role']);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $pegawai->update($data);
        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil diupdate.');
    }

    public function destroy(User $pegawai)
    {
        $pegawai->delete();
        return back()->with('success', 'Pegawai berhasil dihapus.');
    }
}
