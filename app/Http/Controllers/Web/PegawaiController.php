<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=template_pegawai.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['nama', 'email', 'nik', 'nip', 'no_hp', 'jabatan', 'pangkat_gol', 'unit'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Contoh data
            fputcsv($file, ['Budi Santoso', '', '1234567890123456', '198001012005011001', '0812345678', 'Perawat', 'Penata / III-c', 'IGD']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,txt']);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        $header = fgetcsv($handle, 1000, ",");
        
        $count = 0;
        $errors = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            try {
                // index: 0:nama, 1:email, 2:nik, 3:nip, 4:no_hp, 5:jabatan, 6:pangkat_gol, 7:unit
                $nik = trim($data[2]);
                if (empty($nik)) continue;

                $email = trim($data[1]) ?: $nik . '@rsud-baubau.go.id';
                
                User::updateOrCreate(
                    ['nik' => $nik],
                    [
                        'name' => trim($data[0]),
                        'email' => $email,
                        'nip' => trim($data[3]),
                        'no_hp' => trim($data[4]),
                        'jabatan' => trim($data[5]),
                        'pangkat_gol' => trim($data[6]),
                        'unit' => trim($data[7]),
                        'role' => 'pegawai', // Default for import
                        'password' => Hash::make($nik),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $errors++;
            }
        }
        fclose($handle);

        return redirect()->route('pegawai.index')->with('success', "$count pegawai berhasil diimpor. (Gagal: $errors)");
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
