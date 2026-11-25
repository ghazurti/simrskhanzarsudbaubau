<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'pegawai');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%')
                  ->orWhere('unit', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->orderBy('name')->paginate(20));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'nip' => 'required|string|unique:users',
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:100',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'nip' => $request->nip,
            'no_hp' => $request->no_hp,
            'jabatan' => $request->jabatan,
            'unit' => $request->unit,
            'role' => 'pegawai',
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Pegawai berhasil ditambahkan', 'user' => $user], 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'nip' => 'sometimes|string|unique:users,nip,' . $id,
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:100',
        ]);

        $data = $request->only(['name', 'email', 'nip', 'no_hp', 'jabatan', 'unit']);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json(['message' => 'Pegawai berhasil diupdate', 'user' => $user]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Pegawai berhasil dihapus']);
    }

    public function resetDevice(string $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'device_id' => null,
            'device_name' => null,
            'device_bound_at' => null,
        ]);

        return response()->json([
            'message' => 'Perangkat pegawai berhasil direset. Pegawai dapat login di HP baru.',
            'user' => $user->fresh(),
        ]);
    }
}
