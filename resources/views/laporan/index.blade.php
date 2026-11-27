@extends('layouts.app')
@section('title', 'Laporan Absensi')
@section('breadcrumb')Laporan / <span>Laporan Absensi</span>@endsection

@push('styles')
<style>
    .rekap-stat { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
    .rs-box { background:#fff; border:1px solid var(--gray-200); border-radius:12px; padding:16px; display:flex; align-items:center; gap:12px; }
    .rs-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
    .rs-val { font-size:22px; font-weight:800; line-height:1; }
    .rs-label { font-size:11px; color:var(--gray-400); margin-top:3px; }
    @media(max-width:900px) { .rekap-stat { grid-template-columns:repeat(2,1fr); } }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:22px;font-weight:800;color:var(--gray-900)">Laporan Absensi</div>
        <div style="font-size:13px;color:var(--gray-400);margin-top:3px">
            @if(($mode ?? 'riwayat') === 'harian')
                Semua Pegawai &mdash; {{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
            @else
                {{ \Carbon\Carbon::create($tahun, $bulan)->locale('id')->isoFormat('MMMM YYYY') }}
            @endif
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="button" onclick="bukaKoreksiBaru()" class="btn btn-outline" style="border-color:var(--primary);color:var(--primary);font-weight:600">
            <i class="bi bi-pencil-square"></i> Koreksi / Input Manual
        </button>
        <a href="{{ route('laporan.export', request()->query()) }}" class="btn btn-primary">
            <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
        </a>
    </div>
</div>

{{-- Mode Tabs --}}
<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
    <a href="{{ route('laporan.index', ['mode' => 'riwayat', 'bulan' => $bulan, 'tahun' => $tahun, 'unit' => $unit]) }}"
       class="btn {{ ($mode ?? 'riwayat') === 'riwayat' ? 'btn-primary' : 'btn-outline' }}"
       style="padding:9px 16px;font-weight:600">
        <i class="bi bi-table"></i> Riwayat Absensi (Bulanan)
    </a>
    <a href="{{ route('laporan.index', ['mode' => 'harian', 'tanggal' => $tanggal ?? date('Y-m-d'), 'unit' => $unit]) }}"
       class="btn {{ ($mode ?? 'riwayat') === 'harian' ? 'btn-primary' : 'btn-outline' }}"
       style="padding:9px 16px;font-weight:600">
        <i class="bi bi-people-fill"></i> Semua Pegawai Per Tanggal (Belum Absen &amp; Koreksi Massal)
    </a>
</div>

@if(($mode ?? 'riwayat') === 'harian')
{{-- Filter Harian --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <form style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="mode" value="harian">
            <div>
                <label class="form-label" style="font-size:12px">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width:165px">
            </div>
            @if($units->count() > 1)
            <div style="min-width:170px">
                <label class="form-label" style="font-size:12px">Unit</label>
                <select name="unit" class="form-control form-select">
                    <option value="">Semua Unit</option>
                    @foreach($units as $u)
                    <option value="{{ $u }}" {{ $unit==$u?'selected':'' }}>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="min-width:165px">
                <label class="form-label" style="font-size:12px">Status Absen</label>
                <select name="status_harian" class="form-control form-select">
                    <option value="" {{ ($statusHarian ?? '')===''?'selected':'' }}>Semua Pegawai</option>
                    <option value="belum" {{ ($statusHarian ?? '')==='belum'?'selected':'' }}>Belum Absen Saja</option>
                    <option value="sudah" {{ ($statusHarian ?? '')==='sudah'?'selected':'' }}>Sudah Absen Saja</option>
                </select>
            </div>
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:12px">Cari Nama / NIP</label>
                <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Ketik nama atau NIP...">
            </div>
            <button type="submit" class="btn btn-primary" style="padding:10px 20px">
                <i class="bi bi-search"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

{{-- Stats Harian --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
    <div class="rs-box">
        <div class="rs-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="rs-val" style="color:#2563eb">{{ $harianStats['total'] ?? 0 }}</div>
            <div class="rs-label">Total Pegawai</div>
        </div>
    </div>
    <div class="rs-box">
        <div class="rs-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-person-check-fill"></i></div>
        <div>
            <div class="rs-val" style="color:#16a34a">{{ $harianStats['sudah'] ?? 0 }}</div>
            <div class="rs-label">Sudah Absen</div>
        </div>
    </div>
    <div class="rs-box">
        <div class="rs-icon" style="background:#fef2f2;color:#dc2626"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <div class="rs-val" style="color:#dc2626">{{ $harianStats['belum'] ?? 0 }}</div>
            <div class="rs-label">Belum Absen (Bisa Dikoreksi)</div>
        </div>
    </div>
</div>

{{-- Form Koreksi Massal + Tabel Semua Pegawai --}}
<form action="{{ route('laporan.koreksi_massal') }}" method="POST" id="formKoreksiMassal" onsubmit="return cekPilihanMassal()">
    @csrf
    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
    <div class="card" style="margin-bottom:16px;border:1.5px solid #bbf7d0;background:#f0fdf4">
        <div class="card-body" style="padding:14px 18px">
            <div style="font-weight:700;font-size:13px;color:#166534;margin-bottom:10px">
                <i class="bi bi-lightning-charge-fill"></i> Koreksi Massal Sekaligus (Jika Aplikasi Error untuk Banyak Pegawai)
            </div>
            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <div>
                    <label class="form-label" style="font-size:11px">Jam Masuk</label>
                    <input type="time" name="check_in" value="07:30" class="form-control" style="width:120px">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px">Jam Pulang</label>
                    <input type="time" name="check_out" value="16:00" class="form-control" style="width:120px">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px">Status</label>
                    <select name="status" class="form-control form-select" style="width:130px" required>
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpha">Alpha</option>
                    </select>
                </div>
                <div style="flex:1;min-width:200px">
                    <label class="form-label" style="font-size:11px">Alasan Koreksi Massal</label>
                    <input type="text" name="alasan" value="Aplikasi absensi error / gangguan server" class="form-control" required maxlength="255">
                </div>
                <button type="button" onclick="pilihSemuaBelum()" class="btn btn-outline" style="background:#fff;border-color:#16a34a;color:#166534;font-weight:600">
                    Pilih Semua Belum Absen
                </button>
                <button type="submit" class="btn btn-primary" style="font-weight:700">
                    <i class="bi bi-check2-all"></i> Simpan Koreksi Massal (<span id="jumlahDipilih">0</span>)
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-people me-2" style="color:var(--primary)"></i>Daftar Semua Pegawai ({{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }})</span>
            <span style="font-size:12px;color:var(--gray-400)">{{ $harianList->count() }} pegawai ditampilkan</span>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;text-align:center">
                            <input type="checkbox" id="checkAllHarian" onchange="toggleAllHarian(this.checked)">
                        </th>
                        <th>Pegawai</th>
                        <th>NIP</th>
                        <th>Unit</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th style="text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($harianList as $row)
                    @php
                        $u = $row->user;
                        $a = $row->absensi;
                        $jamIn = ($a && $a->check_in) ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '';
                        $jamOut = ($a && $a->check_out) ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '';
                    @endphp
                    <tr style="{{ !$a ? 'background:#fffbfb' : '' }}">
                        <td style="text-align:center">
                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}"
                                class="cb-harian {{ !$a ? 'cb-belum' : 'cb-sudah' }}"
                                onchange="updateJumlahDipilih()">
                        </td>
                        <td style="font-weight:600;font-size:13px">{{ $u->name }}</td>
                        <td style="font-family:monospace;font-size:12px;color:var(--gray-400)">{{ $u->nip ?? '-' }}</td>
                        <td>
                            @if($u->unit)
                            <span style="background:var(--primary-light);color:var(--primary);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600">{{ $u->unit }}</span>
                            @else <span style="color:var(--gray-300)">—</span> @endif
                        </td>
                        <td style="color:#16a34a;font-weight:600">{{ $jamIn ?: '-' }}</td>
                        <td style="color:var(--primary);font-weight:600">{{ $jamOut ?: '-' }}</td>
                        <td>
                            @if($a)
                                <span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span>
                            @else
                                <span style="background:#fee2e2;color:#b91c1c;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700">Belum Absen</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:var(--gray-500);max-width:220px">{{ $a->keterangan ?? '-' }}</td>
                        <td style="text-align:center;white-space:nowrap">
                            <button type="button"
                                onclick="bukaKoreksiRow({{ $u->id }}, '{{ $tanggal }}', '{{ $jamIn ?: '07:30' }}', '{{ $jamOut ?: '16:00' }}', '{{ $a->status ?? 'hadir' }}', '{{ addslashes($u->name) }}')"
                                class="btn btn-outline btn-sm"
                                style="font-size:12px;padding:5px 10px;border-color:{{ !$a ? '#dc2626' : '#cbd5e1' }};color:{{ !$a ? '#dc2626' : 'inherit' }}">
                                <i class="bi bi-pencil-square"></i> {{ !$a ? 'Input Absen' : 'Koreksi' }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:48px;color:var(--gray-400)">Tidak ada pegawai ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

@else
{{-- Filter --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <form style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div>
                <label class="form-label" style="font-size:12px">Bulan</label>
                <select name="bulan" class="form-control form-select" style="width:140px">
                    @for($i=1;$i<=12;$i++)
                    <option value="{{ $i }}" {{ $bulan==$i?'selected':'' }}>
                        {{ \Carbon\Carbon::create(null,$i)->locale('id')->isoFormat('MMMM') }}
                    </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px">Tahun</label>
                <select name="tahun" class="form-control form-select" style="width:100px">
                    @for($y=date('Y');$y>=date('Y')-2;$y--)
                    <option value="{{ $y }}" {{ $tahun==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @if($units->count() > 1)
            <div style="min-width:160px">
                <label class="form-label" style="font-size:12px">Unit</label>
                <select name="unit" class="form-control form-select">
                    <option value="">Semua Unit</option>
                    @foreach($units as $u)
                    <option value="{{ $u }}" {{ $unit==$u?'selected':'' }}>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:12px">Pegawai</label>
                <select name="user_id" class="form-control form-select">
                    <option value="">Semua Pegawai</option>
                    @foreach($pegawais as $p)
                    <option value="{{ $p->id }}" {{ $userId==$p->id?'selected':'' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:140px">
                <label class="form-label" style="font-size:12px">Foto Selfie</label>
                <select name="foto" class="form-control form-select">
                    <option value="">Semua</option>
                    <option value="ada" {{ ($foto ?? '')==='ada'?'selected':'' }}>Ada Foto</option>
                    <option value="tidak" {{ ($foto ?? '')==='tidak'?'selected':'' }}>Tanpa Foto</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding:10px 20px">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request()->hasAny(['bulan','tahun','user_id','unit','foto']))
            <a href="{{ route('laporan.index') }}" class="btn btn-outline" style="padding:10px 16px">Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Rekap Stats --}}
@php
$statusConfig = [
    'hadir'     => ['label'=>'Hadir',     'icon'=>'person-check-fill', 'bg'=>'#f0fdf4','ic'=>'#16a34a','vc'=>'#16a34a'],
    'terlambat' => ['label'=>'Terlambat', 'icon'=>'clock-history',     'bg'=>'#fff7ed','ic'=>'#d97706','vc'=>'#d97706'],
    'izin'      => ['label'=>'Izin',      'icon'=>'calendar-x-fill',   'bg'=>'#eff6ff','ic'=>'#2563eb','vc'=>'#2563eb'],
    'sakit'     => ['label'=>'Sakit',     'icon'=>'heart-pulse-fill',  'bg'=>'#faf5ff','ic'=>'#9333ea','vc'=>'#9333ea'],
    'alpha'     => ['label'=>'Alpha',     'icon'=>'x-circle-fill',     'bg'=>'#fef2f2','ic'=>'#dc2626','vc'=>'#dc2626'],
];
@endphp
<div class="rekap-stat">
    @foreach($statusConfig as $key => $cfg)
    <div class="rs-box">
        <div class="rs-icon" style="background:{{ $cfg['bg'] }};color:{{ $cfg['ic'] }}">
            <i class="bi bi-{{ $cfg['icon'] }}"></i>
        </div>
        <div>
            <div class="rs-val" style="color:{{ $cfg['vc'] }}">{{ $rekap[$key] ?? 0 }}</div>
            <div class="rs-label">{{ $cfg['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-table me-2" style="color:var(--primary)"></i>Detail Absensi</span>
        <span style="font-size:12px;color:var(--gray-400);font-weight:400">{{ $absensis->total() }} record</span>
    </div>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pegawai</th>
                    <th>NIP</th>
                    <th>Unit</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Foto</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th style="text-align:center">Aksi</th>
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
                    $tglIso = \Carbon\Carbon::parse($a->tanggal)->format('Y-m-d');
                    $jamIn = $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '';
                    $jamOut = $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '';
                @endphp
                <tr>
                    <td style="font-weight:600;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($a->tanggal)->locale('id')->isoFormat('ddd, D MMM Y') }}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                                {{ strtoupper(substr($a->user->name ?? 'P', 0, 1)) }}
                            </div>
                            <div style="font-weight:600;font-size:13px">{{ $a->user->name ?? '-' }}</div>
                        </div>
                    </td>
                    <td style="font-family:monospace;font-size:12px;color:var(--gray-400)">{{ $a->user->nip ?? '-' }}</td>
                    <td>
                        @if($a->user->unit ?? null)
                        <span style="background:var(--primary-light);color:var(--primary);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600">
                            {{ $a->user->unit }}
                        </span>
                        @else <span style="color:var(--gray-300)">—</span> @endif
                    </td>
                    <td style="color:#16a34a;font-weight:600">
                        {{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '-' }}
                    </td>
                    <td style="color:var(--primary);font-weight:600">
                        {{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '-' }}
                    </td>
                    <td style="white-space:nowrap">
                        @if($a->foto_check_in || $a->foto_check_out)
                        <div style="display:flex;gap:6px;align-items:center">
                            @if($a->foto_check_in)
                            <button type="button"
                                onclick="lihatFoto('{{ asset('storage/' . $a->foto_check_in) }}', '{{ addslashes($a->user->name ?? '-') }} — Check In ({{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }})')"
                                title="Lihat foto Check In"
                                style="padding:0;border:1.5px solid #bbf7d0;border-radius:6px;overflow:hidden;cursor:pointer;background:#fff;width:36px;height:36px">
                                <img src="{{ asset('storage/' . $a->foto_check_in) }}" alt="Masuk" style="width:100%;height:100%;object-fit:cover;display:block">
                            </button>
                            @endif
                            @if($a->foto_check_out)
                            <button type="button"
                                onclick="lihatFoto('{{ asset('storage/' . $a->foto_check_out) }}', '{{ addslashes($a->user->name ?? '-') }} — Check Out ({{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }})')"
                                title="Lihat foto Check Out"
                                style="padding:0;border:1.5px solid #bfdbfe;border-radius:6px;overflow:hidden;cursor:pointer;background:#fff;width:36px;height:36px">
                                <img src="{{ asset('storage/' . $a->foto_check_out) }}" alt="Pulang" style="width:100%;height:100%;object-fit:cover;display:block">
                            </button>
                            @endif
                        </div>
                        @else
                        <span style="color:var(--gray-300);font-size:12px">—</span>
                        @endif
                    </td>
                    <td style="color:var(--gray-400);font-size:12px">{{ $dur ?? '-' }}</td>
                    <td><span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                    <td style="font-size:12px;color:var(--gray-500);max-width:200px">{{ $a->keterangan ?: '-' }}</td>
                    <td style="text-align:center;white-space:nowrap">
                        <button type="button"
                            onclick="bukaKoreksiRow({{ $a->user_id }}, '{{ $tglIso }}', '{{ $jamIn }}', '{{ $jamOut }}', '{{ $a->status }}', '{{ addslashes($a->user->name ?? '-') }}')"
                            class="btn btn-outline btn-sm"
                            style="font-size:12px;padding:5px 10px;border-color:#cbd5e1"
                            title="Koreksi absen baris ini">
                            <i class="bi bi-pencil-square"></i> Koreksi
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align:center;padding:48px;color:var(--gray-400)">
                        <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:10px;opacity:.3"></i>
                        Tidak ada data untuk periode yang dipilih
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:16px;border-top:1px solid var(--gray-100)">{{ $absensis->links() }}</div>
</div>
@endif

{{-- Modal Koreksi Absensi Kepegawaian --}}
<div id="koreksiModal" onclick="if(event.target===this)tutupKoreksi()" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;max-width:500px;width:100%;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.35)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--gray-200)">
            <div>
                <div id="koreksiModalTitle" style="font-weight:700;font-size:15px;color:var(--gray-900)">Koreksi Absensi Kepegawaian</div>
                <div style="font-size:12px;color:var(--gray-400)">Gunakan jika aplikasi error, gangguan GPS, atau perlu koreksi manual</div>
            </div>
            <button type="button" onclick="tutupKoreksi()" style="border:none;background:none;font-size:22px;line-height:1;cursor:pointer;color:var(--gray-500)">&times;</button>
        </div>
        <form action="{{ route('laporan.koreksi') }}" method="POST" style="padding:18px">
            @csrf
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label" style="font-size:12px">Pegawai</label>
                <select name="user_id" id="korUserId" class="form-control form-select" required>
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach($pegawais as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} {{ $p->unit ? '('.$p->unit.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:12px">Tanggal</label>
                    <input type="date" name="tanggal" id="korTanggal" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:12px">Status Kehadiran</label>
                    <select name="status" id="korStatus" class="form-control form-select" required>
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpha">Alpha</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:12px">Jam Check In</label>
                    <input type="time" name="check_in" id="korCheckIn" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:12px">Jam Check Out</label>
                    <input type="time" name="check_out" id="korCheckOut" class="form-control">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label" style="font-size:12px">Alasan Koreksi (wajib)</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" onclick="isiAlasan('Aplikasi absensi error / gagal kirim')" class="btn btn-outline btn-sm" style="font-size:11px;padding:2px 8px">Aplikasi error</button>
                    <button type="button" onclick="isiAlasan('Gangguan sinyal / GPS tidak terbaca')" class="btn btn-outline btn-sm" style="font-size:11px;padding:2px 8px">Gangguan GPS/sinyal</button>
                    <button type="button" onclick="isiAlasan('Lupa absen pulang (konfirmasi atasan)')" class="btn btn-outline btn-sm" style="font-size:11px;padding:2px 8px">Lupa absen pulang</button>
                </div>
                <input type="text" name="alasan" id="korAlasan" class="form-control" required maxlength="255" placeholder="Contoh: Aplikasi error saat check-in pagi">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" onclick="tutupKoreksi()" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Pratinjau Foto Absensi --}}
<div id="fotoModal" onclick="if(event.target===this)tutupFoto()" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.75);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;max-width:480px;width:100%;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.35)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--gray-200)">
            <div id="fotoModalTitle" style="font-weight:700;font-size:14px;color:var(--gray-900)">Foto Absensi</div>
            <button type="button" onclick="tutupFoto()" style="border:none;background:none;font-size:20px;line-height:1;cursor:pointer;color:var(--gray-500)">&times;</button>
        </div>
        <div style="background:#000;display:flex;align-items:center;justify-content:center;max-height:70vh">
            <img id="fotoModalImg" src="" alt="Foto Absensi" style="max-width:100%;max-height:70vh;object-fit:contain;display:block">
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function lihatFoto(url, title) {
    document.getElementById('fotoModalImg').src = url;
    document.getElementById('fotoModalTitle').textContent = title;
    document.getElementById('fotoModal').style.display = 'flex';
}
function tutupFoto() {
    document.getElementById('fotoModal').style.display = 'none';
    document.getElementById('fotoModalImg').src = '';
}
function bukaKoreksiBaru() {
    document.getElementById('koreksiModalTitle').textContent = 'Input / Koreksi Absensi Manual';
    document.getElementById('korUserId').value = '{{ $userId ?? '' }}';
    document.getElementById('korTanggal').value = '{{ date('Y-m-d') }}';
    document.getElementById('korCheckIn').value = '07:30';
    document.getElementById('korCheckOut').value = '16:00';
    document.getElementById('korStatus').value = 'hadir';
    document.getElementById('korAlasan').value = '';
    document.getElementById('koreksiModal').style.display = 'flex';
}
function bukaKoreksiRow(userId, tanggal, checkIn, checkOut, status, nama) {
    document.getElementById('koreksiModalTitle').textContent = 'Koreksi Absensi — ' + nama;
    document.getElementById('korUserId').value = userId;
    document.getElementById('korTanggal').value = tanggal;
    document.getElementById('korCheckIn').value = checkIn;
    document.getElementById('korCheckOut').value = checkOut;
    document.getElementById('korStatus').value = status;
    document.getElementById('korAlasan').value = '';
    document.getElementById('koreksiModal').style.display = 'flex';
}
function tutupKoreksi() {
    document.getElementById('koreksiModal').style.display = 'none';
}
function isiAlasan(teks) {
    document.getElementById('korAlasan').value = teks;
}
function updateJumlahDipilih() {
    const n = document.querySelectorAll('.cb-harian:checked').length;
    const el = document.getElementById('jumlahDipilih');
    if (el) el.textContent = n;
}
function toggleAllHarian(checked) {
    document.querySelectorAll('.cb-harian').forEach(cb => cb.checked = checked);
    updateJumlahDipilih();
}
function pilihSemuaBelum() {
    document.querySelectorAll('.cb-harian').forEach(cb => cb.checked = cb.classList.contains('cb-belum'));
    updateJumlahDipilih();
}
function cekPilihanMassal() {
    const n = document.querySelectorAll('.cb-harian:checked').length;
    if (n === 0) {
        alert('Pilih minimal 1 pegawai terlebih dahulu.');
        return false;
    }
    return confirm('Simpan koreksi massal untuk ' + n + ' pegawai terpilih?');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { tutupFoto(); tutupKoreksi(); } });
</script>
@endpush
