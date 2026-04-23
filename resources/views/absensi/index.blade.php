@extends('layouts.app')
@section('title', 'Absensi')
@section('breadcrumb')Kehadiran / <span>Absensi</span>@endsection

@push('styles')
<style>
    .page-title { font-size: 22px; font-weight: 800; color: var(--gray-900); margin-bottom: 4px; }
    .page-sub { font-size: 13px; color: var(--gray-400); margin-bottom: 24px; }

    .absensi-grid { 
        display: grid; 
        grid-template-columns: {{ auth()->user()->isAdmin() ? '1fr' : '380px 1fr' }}; 
        gap: 20px; 
    }

    /* Camera & GPS */
    #cam-container { background: #000; border-radius: 10px; overflow: hidden; margin-bottom: 12px; aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center; }
    #video { width: 100%; height: 100%; object-fit: cover; }
    #foto-preview { width: 100%; border-radius: 10px; display: none; }
    .gps-bar { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 9px; font-size: 13px; border: 1.5px solid var(--gray-200); margin-bottom: 14px; transition: all .3s; }
    .gps-bar.ok { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
    .gps-bar.err { border-color: #fecaca; background: #fef2f2; color: #dc2626; }

    .btn-finger { 
        background: #f8fafc; 
        border: 2px dashed var(--gray-200); 
        color: var(--gray-600); 
        padding: 20px; 
        border-radius: 12px; 
        width: 100%; 
        margin-bottom: 20px;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    .btn-finger:hover { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
    .btn-finger i { font-size: 32px; }

    .time-display { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
    .time-box { border: 1.5px solid var(--gray-200); border-radius: 10px; padding: 14px; text-align: center; }
    .time-box .tl { font-size: 11px; color: var(--gray-400); margin-bottom: 4px; }
    .time-box .tv { font-size: 26px; font-weight: 800; }
    .tv.in { color: #16a34a; }
    .tv.out { color: var(--primary); }
    .tv.empty { color: var(--gray-200); }

    @media(max-width:900px) { .absensi-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-title">Absensi</div>
<div class="page-sub">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</div>

<div class="absensi-grid">

    {{-- KIRI: Panel Absensi --}}
    @if(!auth()->user()->isAdmin())
    <div>
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-fingerprint me-2" style="color:var(--primary)"></i>Absensi Hari Ini</span>
                @if($absensiHariIni)
                <span class="badge badge-{{ $absensiHariIni->status }}">{{ ucfirst($absensiHariIni->status) }}</span>
                @endif
            </div>
            <div class="card-body">
                @php
                    $user = auth()->user();
                    $hariIni = \Carbon\Carbon::today()->dayOfWeek;
                    $isWeekend = in_array($hariIni, config('attendance.hari_libur', [0,6]));
                @endphp

                @if($liburHariIni)
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#dc2626;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-calendar-x-fill"></i>
                    Libur Nasional: {{ $liburHariIni->nama_libur }}. Absensi tidak tersedia.
                </div>
                @elseif($user->jenis_absensi === 'normal' && $isWeekend)
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#dc2626;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-house-heart-fill"></i>
                    Hari libur mingguan. Nikmati istirahat Anda!
                </div>
                @elseif($user->jenis_absensi === 'normal')
                @php
                    $jamKantor = config('attendance.jam_kantor')[$hariIni] ?? null;
                    $toleransi = config('attendance.toleransi_menit', 15);
                    $batasTerlambat = $jamKantor ? \Carbon\Carbon::parse(\Carbon\Carbon::today()->toDateString() . ' ' . $jamKantor['masuk'])->addMinutes($toleransi)->format('H:i') : null;
                @endphp
                <div style="background:var(--primary-light);border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:var(--primary);font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-building"></i>
                    @if($jamKantor)
                        Jam Kantor: {{ $jamKantor['masuk'] }} — {{ $jamKantor['keluar'] }}
                        <span style="font-weight:400;color:var(--gray-500);margin-left:4px">(Batas check-in: {{ $batasTerlambat }})</span>
                    @else
                        Jam kantor hari ini tidak terdefinisi.
                    @endif
                </div>
                @elseif($shiftHariIni)
                <div style="background:var(--primary-light);border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:var(--primary);font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-clock"></i>
                    Shift {{ ucfirst($shiftHariIni->jenis_shift) }}: {{ $shiftHariIni->jam_masuk }} — {{ $shiftHariIni->jam_keluar }}
                </div>
                @endif

                {{-- Tombol Fingerprint --}}
                <button type="button" class="btn-finger" onclick="scanFingerprint()">
                    <i class="bi bi-fingerprint"></i>
                    <div style="font-weight: 700; font-size: 14px;">ABSEN SIDIK JARI</div>
                    <div style="font-size: 11px;">Tempelkan jari pada alat U.are.U 4500</div>
                </button>

                <div class="time-display">
                    <div class="time-box">
                        <div class="tl">Check In</div>
                        <div class="tv {{ $absensiHariIni?->check_in ? 'in' : 'empty' }}">
                            {{ $absensiHariIni?->check_in ? \Carbon\Carbon::parse($absensiHariIni->check_in)->format('H:i') : '--:--' }}
                        </div>
                    </div>
                    <div class="time-box">
                        <div class="tl">Check Out</div>
                        <div class="tv {{ $absensiHariIni?->check_out ? 'out' : 'empty' }}">
                            {{ $absensiHariIni?->check_out ? \Carbon\Carbon::parse($absensiHariIni->check_out)->format('H:i') : '--:--' }}
                        </div>
                    </div>
                </div>

                @if($absensiHariIni?->check_in && $absensiHariIni?->check_out)
                <div style="text-align:center;padding:14px;background:#f0fdf4;border-radius:10px;color:#16a34a;font-weight:600;font-size:14px;display:flex;align-items:center;justify-content:center;gap:8px">
                    <i class="bi bi-check-circle-fill"></i> Absensi hari ini selesai!
                </div>
                @else
                {{-- GPS --}}
                <div class="gps-bar" id="gpsBar">
                    <span id="gpsSpinner" style="width:14px;height:14px;border:2px solid #d1d5db;border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0"></span>
                    <span id="gpsText">Mendapatkan lokasi GPS...</span>
                </div>

                {{-- Kamera --}}
                <div style="margin-bottom:14px">
                    <div id="cam-container">
                        <div id="cam-placeholder" style="color:#666;text-align:center">
                            <i class="bi bi-camera" style="font-size:36px;display:block;margin-bottom:8px"></i>
                            <div style="font-size:13px">Buka kamera untuk foto selfie</div>
                        </div>
                        <video id="video" autoplay playsinline style="display:none"></video>
                    </div>
                    <img id="foto-preview" alt="">

                    <div style="display:flex;gap:8px;margin-top:8px">
                        <button onclick="startCamera()" id="btnKamera" class="btn btn-outline btn-sm" style="flex:1">
                            <i class="bi bi-camera"></i> Buka Kamera
                        </button>
                        <button onclick="ambilFoto()" id="btnAmbil" class="btn btn-primary btn-sm" style="flex:1;display:none">
                            <i class="bi bi-camera-fill"></i> Ambil Foto
                        </button>
                        <button onclick="ulangi()" id="btnUlangi" class="btn btn-outline btn-sm" style="display:none">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                @if(!$absensiHariIni?->check_in)
                <form action="{{ route('absensi.checkin') }}" method="POST" enctype="multipart/form-data" id="formCheckin">
                    @csrf
                    <input type="hidden" name="latitude" id="latIn">
                    <input type="hidden" name="longitude" id="lngIn">
                    <input type="hidden" name="shift_id" value="{{ $shiftHariIni?->id }}">
                    <input type="file" name="foto" id="fotoIn" accept="image/*" class="d-none" style="display:none">
                    <button type="submit" class="btn btn-primary" id="btnCheckin" disabled
                        onclick="return submitAbsensi('in')"
                        style="width:100%;justify-content:center;padding:12px;background:#16a34a;border-color:#16a34a">
                        <i class="bi bi-box-arrow-in-right"></i> CHECK IN SEKARANG
                    </button>
                </form>
                @else
                <form action="{{ route('absensi.checkout') }}" method="POST" enctype="multipart/form-data" id="formCheckout">
                    @csrf
                    <input type="hidden" name="latitude" id="latOut">
                    <input type="hidden" name="longitude" id="lngOut">
                    <input type="file" name="foto" id="fotoOut" accept="image/*" style="display:none">
                    <button type="submit" class="btn btn-primary" id="btnCheckout" disabled
                        onclick="return submitAbsensi('out')"
                        style="width:100%;justify-content:center;padding:12px">
                        <i class="bi bi-box-arrow-right"></i> CHECK OUT SEKARANG
                    </button>
                </form>
                @endif
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- KANAN: Riwayat --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-list-check me-2" style="color:var(--primary)"></i>Riwayat Absensi</span>
            <form style="display:flex;gap:8px;align-items:center">
                <select name="bulan" class="form-control form-select" style="width:130px;padding:6px 10px;font-size:13px" onchange="this.form.submit()">
                    @for($i=1;$i<=12;$i++)
                    <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null,$i)->locale('id')->isoFormat('MMMM') }}
                    </option>
                    @endfor
                </select>
                <select name="tahun" class="form-control form-select" style="width:90px;padding:6px 10px;font-size:13px" onchange="this.form.submit()">
                    @for($y=date('Y');$y>=date('Y')-2;$y--)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        @if(auth()->user()->isAdmin())<th>Pegawai</th>@endif
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Durasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($absensis as $a)
                    @php
                        $dur = null;
                        if($a->check_in && $a->check_out) {
                            $m = \Carbon\Carbon::parse($a->check_in)->diffInMinutes(\Carbon\Carbon::parse($a->check_out));
                            $dur = floor($m/60).'j '.($m%60).'m';
                        }
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ \Carbon\Carbon::parse($a->tanggal)->locale('id')->isoFormat('ddd, D MMM Y') }}</td>
                        @if(auth()->user()->isAdmin())
                        <td>
                            <div style="font-weight:600;font-size:13px">{{ $a->user->name ?? '-' }}</div>
                            <div style="font-size:11px;color:var(--gray-400)">{{ $a->user->unit ?? '' }}</div>
                        </td>
                        @endif
                        <td style="color:#16a34a;font-weight:600">{{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '-' }}</td>
                        <td style="color:var(--primary)">
                            {{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '-' }}
                            @if($a->is_psw)
                                <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 4px;border-radius:4px;font-weight:700;margin-left:4px">PSW</span>
                            @endif
                        </td>
                        <td style="color:var(--gray-400);font-size:12px">{{ $dur ?? '-' }}</td>
                        <td>
                            @if($a->is_lupa_absen)
                                <span class="badge" style="background:#fef2f2;color:#dc2626">Lupa Pulang</span>
                            @else
                                <span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">
                            <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3"></i>
                            Tidak ada data absensi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px;border-top:1px solid var(--gray-100)">{{ $absensis->links() }}</div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@push('scripts')
@if(!auth()->user()->isAdmin())
<script>
let stream = null, capturedBlob = null, gpsLat = null, gpsLng = null;

navigator.geolocation.getCurrentPosition(pos => {
    gpsLat = pos.coords.latitude;
    gpsLng = pos.coords.longitude;
    const jarak = hitungJarak(gpsLat, gpsLng, {{ config('attendance.latitude') }}, {{ config('attendance.longitude') }});
    const bar = document.getElementById('gpsBar');
    document.getElementById('gpsSpinner').remove();
    if (jarak <= {{ config('attendance.radius') }}) {
        bar.className = 'gps-bar ok';
        document.getElementById('gpsText').innerHTML = `<i class="bi bi-geo-alt-fill"></i> Lokasi valid — ${Math.round(jarak)} meter dari Lokasi`;
    } else {
        bar.className = 'gps-bar err';
        document.getElementById('gpsText').innerHTML = `<i class="bi bi-geo-alt"></i> Di luar area absen — ${Math.round(jarak)} meter`;
    }
    updateBtn();
}, () => {
    document.getElementById('gpsSpinner').remove();
    document.getElementById('gpsText').innerHTML = '<i class="bi bi-exclamation-circle"></i> GPS tidak tersedia';
    document.getElementById('gpsBar').className = 'gps-bar err';
}, { enableHighAccuracy: true });

function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } }).then(s => {
        stream = s;
        document.getElementById('cam-placeholder').style.display = 'none';
        const v = document.getElementById('video');
        v.srcObject = s; v.style.display = 'block';
        document.getElementById('btnKamera').style.display = 'none';
        document.getElementById('btnAmbil').style.display = 'flex';
    }).catch(() => alert('Izinkan akses kamera di browser Anda.'));
}

function ambilFoto() {
    const v = document.getElementById('video');
    const c = document.createElement('canvas');
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    c.toBlob(blob => {
        capturedBlob = blob;
        document.getElementById('foto-preview').src = c.toDataURL('image/jpeg');
        document.getElementById('foto-preview').style.display = 'block';
        document.getElementById('cam-container').style.display = 'none';
        document.getElementById('btnAmbil').style.display = 'none';
        document.getElementById('btnUlangi').style.display = 'flex';
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
        updateBtn();
    }, 'image/jpeg', 0.8);
}

function ulangi() {
    capturedBlob = null;
    document.getElementById('foto-preview').style.display = 'none';
    document.getElementById('cam-container').style.display = 'flex';
    document.getElementById('cam-placeholder').style.display = 'block';
    document.getElementById('video').style.display = 'none';
    document.getElementById('btnUlangi').style.display = 'none';
    document.getElementById('btnKamera').style.display = 'flex';
    updateBtn();
}

function updateBtn() {
    const ready = capturedBlob && gpsLat;
    ['btnCheckin','btnCheckout'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !ready;
    });
}

function submitAbsensi(type) {
    if (!capturedBlob) { alert('Ambil foto selfie terlebih dahulu.'); return false; }
    if (!gpsLat) { alert('GPS belum tersedia.'); return false; }
    const file = new File([capturedBlob], 'foto.jpg', { type: 'image/jpeg' });
    const dt = new DataTransfer(); dt.items.add(file);
    if (type === 'in') {
        document.getElementById('latIn').value = gpsLat;
        document.getElementById('lngIn').value = gpsLng;
        document.getElementById('fotoIn').files = dt.files;
    } else {
        document.getElementById('latOut').value = gpsLat;
        document.getElementById('lngOut').value = gpsLng;
        document.getElementById('fotoOut').files = dt.files;
    }
    return true;
}

function hitungJarak(lat1,lon1,lat2,lon2) {
    const R=6371000,dLat=(lat2-lat1)*Math.PI/180,dLon=(lon2-lon1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

// Logika Sidik Jari
async function scanFingerprint() {
    Swal.fire({
        title: 'Menunggu Sidik Jari...',
        text: 'Silakan tempelkan jari Anda pada alat scanner',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Simulasi Panggilan ke Middleware Lokal (localhost)
        // Umumnya SDK Digital Persona menyediakan listener di port tertentu
        const response = await fetch('http://localhost:14500/capture', { // Port simulasi middleware
            method: 'GET',
            timeout: 10000
        });

        const data = await response.json();
        
        if (data.status === 'success') {
            submitFingerprint(data.template);
        } else {
            Swal.fire('Gagal', 'Sidik jari tidak terbaca dengan jelas', 'error');
        }
    } catch (err) {
        // Karena ini demo, jika middleware tidak ada, kita simulasi sukses
        console.warn('Middleware tidak ditemukan, menjalankan simulasi...');
        setTimeout(() => {
            Swal.fire({
                icon: 'question',
                title: 'Simulasi Sidik Jari',
                text: 'Koneksi ke alat tidak ditemukan. Jalankan simulasi absen?',
                showCancelButton: true,
                confirmButtonText: 'Ya, Absen (Simulasi)'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitFingerprint('SAMPLE_FINGERPRINT_TEMPLATE_DATA');
                }
            });
        }, 1500);
    }
}

async function submitFingerprint(template) {
    try {
        const response = await fetch('{{ route("fingerprint.attendance") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ fingerprint_data: template })
        });

        const result = await response.json();

        if (response.ok) {
            Swal.fire('Berhasil!', result.message, 'success').then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Gagal', result.message || 'Terjadi kesalahan', 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Gagal menghubungkan ke server absensi', 'error');
    }
}
</script>
@endif
@endpush
