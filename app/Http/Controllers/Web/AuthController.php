<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function profil()
    {
        return view('auth.profil', ['user' => auth()->user()]);
    }

    public function updateProfil(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email,' . $user->id,
            'no_hp'  => 'nullable|string|max:20',
            'foto'   => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'no_hp']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('foto-profil', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function gantiPassword()
    {
        return view('auth.ganti-password');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'password_lama' => 'required',
            'password'      => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function setJenisAbsensi(Request $request)
    {
        $user = auth()->user();

        if ($user->jenis_absensi_locked) {
            return back()->with('error', 'Jenis absensi Anda sudah terkunci. Hubungi Admin/Kepegawaian jika perlu diubah.');
        }

        $request->validate([
            'jenis_absensi' => 'required|in:normal,shift',
        ]);

        $user->update([
            'jenis_absensi' => $request->jenis_absensi,
            'jenis_absensi_locked' => true,
        ]);

        $label = $request->jenis_absensi === 'shift' ? 'Pegawai Shift' : 'Jam Kantor Normal';
        return back()->with('success', "Jenis absensi Anda telah disimpan sebagai {$label} dan otomatis terkunci.");
    }
}
