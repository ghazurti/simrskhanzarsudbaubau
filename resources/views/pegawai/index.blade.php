@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('breadcrumb')Manajemen / <span>Pegawai</span>@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
    <div>
        <h1 style="margin:0;font-size:26px;font-weight:800;color:var(--gray-900);letter-spacing:-0.5px">Data Pegawai</h1>
        <p style="margin:4px 0 0;font-size:14px;color:var(--gray-500)">Kelola profil dan informasi kepegawaian RSUD Kota Baubau</p>
    </div>
    <div style="display:flex;gap:10px">
        <button type="button" class="btn" onclick="openImportModal()" style="padding:12px 20px;border-radius:12px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;font-weight:600;display:flex;align-items:center;gap:8px">
            <i class="bi bi-file-earmark-arrow-up"></i> Impor Excel / CSV
        </button>
        <a href="{{ route('pegawai.create') }}" class="btn btn-primary" style="padding:12px 20px;border-radius:12px;box-shadow:0 10px 15px -3px rgba(37,99,235,0.2)">
            <i class="bi bi-plus-lg"></i> Tambah Pegawai
        </a>
    </div>
</div>

<div class="card" style="border:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);border-radius:16px;overflow:hidden">
    <div class="card-header" style="background:#fff;padding:20px 24px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
        <div>
            <span style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;background:var(--gray-50);border-radius:20px;font-size:13px;font-weight:500;color:var(--gray-600)">
                <i class="bi bi-person-fill" style="color:var(--primary)"></i>
                Total {{ $pegawais->total() }} Pegawai
            </span>
        </div>
        <form style="display:flex;gap:10px">
            <div class="input-group" style="width:300px;position:relative">
                <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gray-400);z-index:10;font-size:14px"></i>
                <input type="text" name="search" class="form-control" 
                    style="padding:10px 14px 10px 40px;border-radius:10px;font-size:14px;border:1px solid var(--gray-200);width:100%"
                    placeholder="Cari Nama, NIK, NIP, atau Unit..." value="{{ request('search') }}">
            </div>
            <button class="btn btn-primary" style="padding:10px 18px;border-radius:10px;font-weight:600">Cari</button>
            @if(request('search'))
            <a href="{{ route('pegawai.index') }}" class="btn" style="padding:10px 14px;background:var(--gray-100);border-radius:10px;color:var(--gray-600)">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </form>
    </div>

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--gray-50)">
                    <th style="padding:16px 24px;text-align:left;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px">Profil Pegawai</th>
                    <th style="padding:16px 24px;text-align:left;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px">Identitas</th>
                    <th style="padding:16px 24px;text-align:left;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px">Kepegawaian</th>
                    <th style="padding:16px 24px;text-align:left;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px">Kontak</th>
                    <th style="padding:16px 24px;text-align:right;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px">Aksi</th>
                </tr>
            </thead>
            <tbody style="background:#fff">
                @forelse($pegawais as $p)
                <tr style="border-bottom:1px solid var(--gray-50);transition:background 0.25s" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
                    <td style="padding:16px 24px">
                        <div style="display:flex;align-items:center;gap:14px">
                            <div style="width:44px;height:44px;border-radius:12px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;text-shadow:0 1px 2px rgba(0,0,0,0.1)">
                                {{ strtoupper(substr($p->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:15px;color:var(--gray-900)">{{ $p->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:16px 24px">
                        <div style="display:flex;flex-direction:column;gap:4px">
                            <div style="font-size:13.5px;color:var(--gray-900);font-weight:600"><small style="color:var(--gray-400);font-weight:400">NIK:</small> {{ $p->nik ?? '-' }}</div>
                            <div style="font-size:13.5px;color:var(--gray-600)"><small style="color:var(--gray-400);font-weight:400">NIP:</small> {{ $p->nip ?? '-' }}</div>
                        </div>
                    </td>
                    <td style="padding:16px 24px">
                        <div style="font-size:13.5px;font-weight:600;color:var(--gray-800)">{{ $p->jabatan ?? '-' }}</div>
                        <div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap">
                            @if($p->unit)
                            <span style="background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:6px;font-size:11.5px;font-weight:700;border:1px solid #e2e8f0;display:inline-block">
                                {{ $p->unit }}
                            </span>
                            @endif

                            @if($p->role === 'kepala_unit')
                            <span style="background:#ecfdf5;color:#059669;padding:4px 10px;border-radius:6px;font-size:11.5px;font-weight:700;border:1px solid #a7f3d0;display:inline-block">
                                Kepala Unit
                            </span>
                            @elseif($p->role === 'admin')
                            <span style="background:#fff7ed;color:#c2410c;padding:4px 10px;border-radius:6px;font-size:11.5px;font-weight:700;border:1px solid #fed7aa;display:inline-block">
                                Admin
                            </span>
                            @endif
                        </div>
                    </td>
                    <td style="padding:16px 24px">
                        <div style="display:flex;flex-direction:column;gap:4px">
                            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-600)">
                                <i class="bi bi-phone" style="font-size:14px;color:var(--gray-400)"></i>
                                {{ $p->no_hp ?? '-' }}
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-400)">
                                <i class="bi bi-envelope" style="font-size:13px;color:var(--gray-300)"></i>
                                {{ $p->email }}
                            </div>
                        </div>
                    </td>
                    <td style="padding:16px 24px;text-align:right">
                        <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center">
                            <button type="button" onclick="openEnrollModal('{{ $p->id }}', '{{ addslashes($p->name) }}')" class="btn" style="padding:8px 12px;background:#fdf4ff;color:#a21caf;border:1px solid #f5d0fe;border-radius:10px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s" onmouseover="this.style.background='#f5d0fe'" onmouseout="this.style.background='#fdf4ff'">
                                <i class="bi bi-fingerprint"></i> Daftar Jari
                            </button>
                            <a href="{{ route('pegawai.edit', $p) }}" class="btn" style="padding:8px 12px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:10px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s" onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#f0f9ff'">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <form action="{{ route('pegawai.destroy', $p) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data {{ addslashes($p->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn" style="padding:8px 12px;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:10px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s;cursor:pointer" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fef2f2'">
                                    <i class="bi bi-trash3"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:80px 24px;text-align:center">
                        <div style="display:inline-flex;width:80px;height:80px;background:var(--gray-50);border-radius:50%;align-items:center;justify-content:center;margin-bottom:20px">
                            <i class="bi bi-people" style="font-size:40px;color:var(--gray-300)"></i>
                        </div>
                        <div style="font-weight:700;font-size:18px;color:var(--gray-800)">Tidak Ada Data Pegawai</div>
                        <p style="color:var(--gray-500);margin-top:8px;max-width:300px;margin-left:auto;margin-right:auto">Gunakan tombol "Tambah Pegawai" untuk menambahkan data baru atau sesuaikan pencarian Anda.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($pegawais->hasPages())
    <div style="padding:20px 24px;background:#fefefe;border-top:1px solid var(--gray-100);display:flex;justify-content:center">
        {{ $pegawais->links() }}
    </div>
    @endif
</div>
@endsection

{{-- Modal Import --}}
<div id="importModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:100%;max-width:500px;border-radius:16px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
        <div style="padding:24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:18px;font-weight:800;color:#1e293b">Impor Data Pegawai</h3>
            <button onclick="closeImportModal()" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:20px"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('pegawai.import') }}" method="POST" enctype="multipart/form-data" style="padding:24px">
            @csrf
            <div style="margin-bottom:16px">
                <label style="display:block;font-weight:600;font-size:14px;color:#475569;margin-bottom:8px">
                    Pilih File <span style="color:#dc2626">*</span>
                </label>
                <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                    class="form-control" style="padding:10px;border-radius:8px;border:1px solid #e2e8f0;width:100%">
                <p style="font-size:12px;color:#64748b;margin-top:8px">Format yang didukung: <strong>.xlsx, .xls, .csv</strong> (maks. 5 MB)</p>
            </div>

            {{-- Kolom info --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:16px;font-size:12px;color:#475569;line-height:1.8">
                <div style="font-weight:700;margin-bottom:6px;color:#1e293b"><i class="bi bi-table me-1"></i>Kolom yang diperlukan:</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 16px">
                    <span><code>nama</code> — wajib</span>
                    <span><code>email</code> — opsional</span>
                    <span><code>nik</code> — wajib (unik)</span>
                    <span><code>nip</code> — opsional</span>
                    <span><code>no_hp</code> — opsional</span>
                    <span><code>jabatan</code> — opsional</span>
                    <span><code>pangkat_gol</code> — opsional</span>
                    <span><code>unit</code> — opsional</span>
                    <span><code>jenis_absensi</code> — <em>normal</em> / <em>shift</em></span>
                    <span><code>role</code> — <em>pegawai</em> / <em>kepala_unit</em></span>
                </div>
            </div>

            <a href="{{ route('pegawai.template') }}" style="display:inline-flex;align-items:center;gap:8px;color:#16a34a;font-weight:700;font-size:13px;text-decoration:none;margin-bottom:20px">
                <i class="bi bi-file-earmark-excel-fill" style="font-size:16px"></i> Unduh Template Excel
            </a>

            <div style="display:flex;gap:12px;justify-content:flex-end;padding-top:16px;border-top:1px solid #f1f5f9">
                <button type="button" onclick="closeImportModal()" style="padding:10px 20px;border-radius:10px;background:#f1f5f9;border:none;color:#475569;font-weight:600;cursor:pointer">Batal</button>
                <button type="submit" style="padding:10px 24px;border-radius:10px;background:var(--primary);border:none;color:#fff;font-weight:700;cursor:pointer;box-shadow:0 4px 6px -1px rgba(37,99,235,0.2)">
                    <i class="bi bi-upload me-1"></i> Mulai Impor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openImportModal() {
        document.getElementById('importModal').style.display = 'flex';
    }
    function closeImportModal() {
        document.getElementById('importModal').style.display = 'none';
    }
    // Close modal when clicking outside
    window.onclick = function(event) {
        let modal = document.getElementById('importModal');
        let enrollModal = document.getElementById('enrollModal');
        if (event.target == modal) closeImportModal();
        if (event.target == enrollModal) closeEnrollModal();
    }

    // Fingerprint Enrollment
    let activeUserId = null;

    function openEnrollModal(id, name) {
        activeUserId = id;
        document.getElementById('enrollName').innerText = name;
        document.getElementById('enrollModal').style.display = 'flex';
        document.getElementById('enrollStatus').innerHTML = '<i class="bi bi-info-circle"></i> Klik tombol di bawah untuk mulai rekam';
        document.getElementById('enrollStatus').className = 'status-box info';
    }

    function closeEnrollModal() {
        document.getElementById('enrollModal').style.display = 'none';
    }

    async function startEnroll() {
        const statusEl = document.getElementById('enrollStatus');
        statusEl.innerHTML = '<span class="enroll-spinner"></span> Menunggu sidik jari ditempelkan (3x scan)...';
        statusEl.className = 'status-box info';

        try {
            // Simulasi pendaftaran ke middleware lokal
            const response = await fetch('http://localhost:14500/enroll', { method: 'GET' });
            const data = await response.json();

            if (data.status === 'success') {
                saveEnrollment(data.template);
            } else {
                statusEl.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Gagal: ' + data.message;
                statusEl.className = 'status-box error';
            }
        } catch (err) {
            console.warn('Middleware tidak terdeteksi, simulasi pendaftaran...');
            setTimeout(() => {
                if (confirm('Jalankan simulasi pendaftaran sidik jari?')) {
                    saveEnrollment('SIMULATED_TEMPLATE_DATA_' + Math.random());
                } else {
                    statusEl.innerHTML = '<i class="bi bi-x-circle"></i> Pendaftaran dibatalkan';
                    statusEl.className = 'status-box error';
                }
            }, 1000);
        }
    }

    async function saveEnrollment(template) {
        const statusEl = document.getElementById('enrollStatus');
        statusEl.innerHTML = '<span class="enroll-spinner"></span> Menyimpan ke server...';

        try {
            const response = await fetch('{{ route("fingerprint.enroll") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    user_id: activeUserId,
                    fingerprint_data: template,
                    fingerprint_id: 'FP-' + activeUserId
                })
            });

            const result = await response.json();
            if (response.ok) {
                statusEl.innerHTML = '<i class="bi bi-check-circle"></i> Berhasil Terdaftar!';
                statusEl.className = 'status-box success';
                setTimeout(() => {
                    closeEnrollModal();
                    Swal.fire('Berhasil', 'Sidik jari ' + document.getElementById('enrollName').innerText + ' telah terdaftar', 'success');
                }, 1500);
            } else {
                statusEl.innerHTML = '<i class="bi bi-x-circle"></i> Gagal Simpan: ' + result.message;
                statusEl.className = 'status-box error';
            }
        } catch (err) {
            statusEl.innerHTML = '<i class="bi bi-exclamation-octagon"></i> Kesalahan Server';
            statusEl.className = 'status-box error';
        }
    }
</script>

{{-- Modal Enroll --}}
<div id="enrollModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:100%;max-width:450px;border-radius:16px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:17px;font-weight:800;color:#1e293b">Pendaftaran Sidik Jari</h3>
            <button onclick="closeEnrollModal()" style="background:none;border:none;color:#94a3b8;cursor:pointer"><i class="bi bi-x-lg"></i></button>
        </div>
        <div style="padding:24px;text-align:center">
            <div style="font-size:14px;color:#64748b;margin-bottom:4px">Registrasi Sidik Jari untuk:</div>
            <div id="enrollName" style="font-size:18px;font-weight:800;color:var(--primary);margin-bottom:20px">Nama Pegawai</div>
            
            <div id="enrollStatus" class="status-box info">
                <i class="bi bi-info-circle"></i> Klik mulai untuk merekam
            </div>

            <div style="margin-top:30px;display:flex;flex-direction:column;gap:12px">
                <button type="button" onclick="startEnroll()" style="padding:14px;background:var(--primary);color:#fff;border:none;border-radius:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px">
                    <i class="bi bi-play-fill" style="font-size:18px"></i> MULAI REKAM SEKARANG
                </button>
                <button type="button" onclick="closeEnrollModal()" style="padding:12px;background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;border-radius:12px;font-weight:600;cursor:pointer">Batal</button>
            </div>
        </div>
    </div>
</div>

<style>
    .status-box { padding:14px; border-radius:12px; font-size:13px; font-weight:600; display:flex; align-items:center; justify-content:center; gap:10px; margin-top:10px; }
    .status-box.info { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
    .status-box.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .status-box.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .enroll-spinner { width:16px; height:16px; border:2px solid #d1d5db; border-top-color:var(--primary); border-radius:50%; animation:spin .7s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
</style>
