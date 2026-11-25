@extends('layouts.app')
@section('title', 'Profil Saya')
@section('breadcrumb')<span>Profil Saya</span>@endsection

@section('content')
<div style="max-width:600px;margin:0 auto">
    <div style="font-size:22px;font-weight:800;color:var(--gray-900);margin-bottom:24px">Profil Saya</div>

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    </div>
    @endif

    <div class="card">
        <div class="card-body" style="padding:24px">

            {{-- Foto Profil --}}
            <div style="display:flex;align-items:center;gap:20px;margin-bottom:28px;padding-bottom:24px;border-bottom:1px solid var(--gray-200)">
                <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    @if($user->foto)
                        <img src="{{ asset('storage/' . $user->foto) }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <i class="bi bi-person-fill" style="font-size:36px;color:var(--primary)"></i>
                    @endif
                </div>
                <div>
                    <div style="font-size:18px;font-weight:700;color:var(--gray-900)">{{ $user->name }}</div>
                    <div style="font-size:13px;color:var(--gray-400);margin-top:2px">{{ $user->jabatan ?? '-' }} • {{ $user->unit ?? '-' }}</div>
                    <div style="font-size:12px;color:var(--gray-400);margin-top:2px">NIK: {{ $user->nik ?? '-' }} | NIP: {{ $user->nip ?? '-' }}</div>
                </div>
            </div>

            {{-- Form Update --}}
            <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data">
                @csrf

                <div style="display:flex;flex-direction:column;gap:16px">
                    <div>
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')<div style="color:#dc2626;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')<div style="color:#dc2626;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror"
                            value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xx...">
                        @error('no_hp')<div style="color:#dc2626;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">Foto Profil</label>
                        <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror"
                            accept="image/jpeg,image/jpg,image/png">
                        <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Format JPG/PNG, maks 2MB. Kosongkan jika tidak ingin mengubah foto.</div>
                        @error('foto')<div style="color:#dc2626;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    {{-- Info readonly --}}
                    <div style="background:var(--gray-50);border-radius:8px;padding:16px;border:1px solid var(--gray-200)">
                        <div style="font-size:12px;font-weight:600;color:var(--gray-500);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">
                            <i class="bi bi-lock me-1"></i> Data yang dikelola kepegawaian
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px">
                            <div><span style="color:var(--gray-400)">Jabatan:</span> <span style="font-weight:500">{{ $user->jabatan ?? '-' }}</span></div>
                            <div><span style="color:var(--gray-400)">Unit:</span> <span style="font-weight:500">{{ $user->unit ?? '-' }}</span></div>
                            <div><span style="color:var(--gray-400)">Status:</span> <span style="font-weight:500">{{ $user->pangkat_gol ?? '-' }}</span></div>
                            <div><span style="color:var(--gray-400)">NIP:</span> <span style="font-weight:500">{{ $user->nip ?? '-' }}</span></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:24px;display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn" style="background:var(--gray-100);color:var(--gray-700)">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Link ganti password --}}
    <div style="margin-top:12px;text-align:center;font-size:13px;color:var(--gray-400)">
        Ingin ganti password? <a href="{{ route('profil.ganti-password') }}" style="color:var(--primary);font-weight:600">Klik di sini</a>
    </div>
</div>
@endsection
