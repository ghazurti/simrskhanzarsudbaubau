@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('breadcrumb')Manajemen / <a href="{{ route('pegawai.index') }}" style="color:inherit">Pegawai</a> / <span>Tambah</span>@endsection

@section('content')
<div style="margin-bottom:28px">
    <h1 style="margin:0;font-size:26px;font-weight:800;color:var(--gray-900);letter-spacing:-0.5px">Tambah Pegawai Baru</h1>
    <p style="margin:4px 0 0;font-size:14px;color:var(--gray-500)">Lengkapi formulir di bawah ini untuk mendaftarkan pegawai baru ke sistem.</p>
</div>

<div style="max-width:900px">
    <form action="{{ route('pegawai.store') }}" method="POST">
        @csrf
        
        {{-- Section 1: Informasi Pribadi --}}
        <div class="card" style="border:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border-radius:16px;margin-bottom:24px">
            <div class="card-header" style="background:transparent;padding:20px 24px;border-bottom:1px solid var(--gray-100)">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:36px;height:36px;border-radius:10px;background:#f0f9ff;color:#0369a1;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-person-badge" style="font-size:18px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:var(--gray-900)">Informasi Pribadi</div>
                        <div style="font-size:12px;color:var(--gray-400)">Data identitas dasar pegawai</div>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:24px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Nama Lengkap <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" 
                            placeholder="Masukkan nama lengkap sesuai KTP" required
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                        @error('name')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">NIK <span style="color:#dc2626">*</span></label>
                        <input type="text" name="nik" class="form-control" value="{{ old('nik') }}" 
                            placeholder="16 Digit Nomor Induk Kependudukan" required
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                        @error('nik')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">No. WhatsApp / HP</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}" 
                            placeholder="Contoh: 08123456789"
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                        @error('no_hp')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Detail Kepegawaian --}}
        <div class="card" style="border:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border-radius:16px;margin-bottom:24px">
            <div class="card-header" style="background:transparent;padding:20px 24px;border-bottom:1px solid var(--gray-100)">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:36px;height:36px;border-radius:10px;background:#f0fdf4;color:#16a34a;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-briefcase" style="font-size:18px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:var(--gray-900)">Detail Kepegawaian</div>
                        <div style="font-size:12px;color:var(--gray-400)">Instansi dan jabatan resmi</div>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:24px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">NIP (Opsional)</label>
                        <input type="text" name="nip" class="form-control" value="{{ old('nip') }}" 
                            placeholder="Nomor Induk Pegawai (PNS)"
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                        @error('nip')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Unit / Departemen</label>
                        <select name="unit" class="form-control form-select" style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200);height:auto">
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->nama }}" {{ old('unit') == $dept->nama ? 'selected' : '' }}>
                                    {{ $dept->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('unit')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" value="{{ old('jabatan') }}" 
                            placeholder="Contoh: Perawat Ahli Muda"
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Pangkat / Golongan</label>
                        <input type="text" name="pangkat_gol" class="form-control" value="{{ old('pangkat_gol') }}" 
                            placeholder="Contoh: Penata / III-c"
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Jenis Absensi --}}
        <div class="card" style="border:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border-radius:16px;margin-bottom:24px">
            <div class="card-header" style="background:transparent;padding:20px 24px;border-bottom:1px solid var(--gray-100)">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:36px;height:36px;border-radius:10px;background:#fff7ed;color:#d97706;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-clock-history" style="font-size:18px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:var(--gray-900)">Jenis Absensi <span style="color:#dc2626">*</span></div>
                        <div style="font-size:12px;color:var(--gray-400)">Menentukan aturan waktu kehadiran pegawai</div>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:24px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <label id="card_normal" onclick="highlightCard('normal')" style="cursor:pointer;border:2px solid {{ old('jenis_absensi','normal')=='normal'?'var(--primary)':'var(--gray-200)' }};border-radius:12px;padding:18px;background:{{ old('jenis_absensi','normal')=='normal'?'var(--primary-light)':'#fff' }};transition:.2s">
                        <input type="radio" name="jenis_absensi" value="normal" {{ old('jenis_absensi','normal')=='normal'?'checked':'' }} style="display:none">
                        <div style="font-weight:700;font-size:15px;color:var(--gray-900);margin-bottom:6px"><i class="bi bi-building me-2" style="color:var(--primary)"></i>Normal</div>
                        <div style="font-size:12px;color:var(--gray-500)">Jam kantor: Sen–Kam 07:30–16:00, Jum 07:30–16:30. Libur Sabtu & Minggu.</div>
                    </label>
                    <label id="card_shift" onclick="highlightCard('shift')" style="cursor:pointer;border:2px solid {{ old('jenis_absensi')=='shift'?'var(--primary)':'var(--gray-200)' }};border-radius:12px;padding:18px;background:{{ old('jenis_absensi')=='shift'?'var(--primary-light)':'#fff' }};transition:.2s">
                        <input type="radio" name="jenis_absensi" value="shift" {{ old('jenis_absensi')=='shift'?'checked':'' }} style="display:none">
                        <div style="font-weight:700;font-size:15px;color:var(--gray-900);margin-bottom:6px"><i class="bi bi-arrow-repeat me-2" style="color:#d97706"></i>Shift</div>
                        <div style="font-size:12px;color:var(--gray-500)">Mengikuti jadwal shift yang ditetapkan. Hari libur = tidak ada jadwal shift.</div>
                    </label>
                </div>
                @error('jenis_absensi')<div style="color:#dc2626;font-size:12px;margin-top:8px;font-weight:500">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Section 4: Akun Sistem --}}
        <div class="card" style="border:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border-radius:16px;margin-bottom:32px">
            <div class="card-header" style="background:transparent;padding:20px 24px;border-bottom:1px solid var(--gray-100)">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:36px;height:36px;border-radius:10px;background:#faf5ff;color:#9333ea;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-shield-lock" style="font-size:18px"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;color:var(--gray-900)">Akun Sistem</div>
                        <div style="font-size:12px;color:var(--gray-400)">Kredensial untuk akses aplikasi</div>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:24px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Alamat Email (Opsional)</label>
                        <input type="email" name="email" id="email_field" class="form-control" value="{{ old('email') }}" 
                            placeholder="Kosongkan untuk otomatis pakai [NIK]@rsud-baubau.go.id"
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                        @error('email')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Role / Hak Akses <span style="color:#dc2626">*</span></label>
                        <select name="role" class="form-control form-select" required style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200);height:auto">
                            <option value="pegawai" {{ old('role') == 'pegawai' ? 'selected' : '' }}>Pegawai (Default)</option>
                            <option value="kepala_unit" {{ old('role') == 'kepala_unit' ? 'selected' : '' }}>Kepala Unit</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                        </select>
                        @error('role')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Password (Opsional)</label>
                        <div style="position:relative">
                            <input type="password" name="password" id="pass_field" class="form-control" 
                                placeholder="Default: rsud123"
                                style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200);width:100%">
                            <button type="button" onclick="togglePass('pass_field')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray-400);cursor:pointer">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')<div style="color:#dc2626;font-size:12px;margin-top:6px;font-weight:500">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;margin-bottom:8px;display:block">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" 
                            placeholder="Ulangi jika mengisi password"
                            style="padding:12px 16px;border-radius:10px;border:1.5px solid var(--gray-200)">
                    </div>
                </div>
            </div>
        </div>

        <script>
            function highlightCard(val) {
                document.querySelectorAll('[id^="card_"]').forEach(el => {
                    el.style.borderColor = 'var(--gray-200)';
                    el.style.background = '#fff';
                });
                const card = document.getElementById('card_' + val);
                card.style.borderColor = 'var(--primary)';
                card.style.background = 'var(--primary-light)';
                card.querySelector('input[type=radio]').checked = true;
            }

            function togglePass(id) {
                const el = document.getElementById(id);
                el.type = el.type === 'password' ? 'text' : 'password';
            }

            // Auto-fill email based on NIK
            document.querySelector('input[name="nik"]').addEventListener('input', function(e) {
                const nik = e.target.value.trim();
                const emailField = document.getElementById('email_field');
                if (nik.length >= 4 && emailField.value === '') {
                    // pre-fill placeholder would be better, but user asked for easy, so lets just hint or fill
                }
            });
        </script>

        @if($errors->has('error'))
        <div class="alert alert-danger" style="padding:12px 16px;border-radius:10px;margin-bottom:16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">
            <i class="bi bi-exclamation-circle me-2"></i> {{ $errors->first('error') }}
        </div>
        @endif

        <div style="display:flex;gap:12px;align-items:center;margin-bottom:40px">
            <button type="submit" class="btn btn-primary" style="padding:14px 32px;border-radius:12px;font-weight:700;font-size:15px;box-shadow:0 10px 15px -3px rgba(37,99,235,0.2)">
                <i class="bi bi-check-circle-fill me-2"></i> Daftarkan Pegawai
            </button>
            <a href="{{ route('pegawai.index') }}" class="btn" style="padding:14px 24px;border-radius:12px;background:var(--gray-100);color:var(--gray-600);font-weight:600;text-decoration:none">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
