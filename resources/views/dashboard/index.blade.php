@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb')<span>Dashboard</span>@endsection

@push('styles')
<style>
    .page-header { margin-bottom: 24px; }
    .page-title { font-size: 24px; font-weight: 800; color: var(--gray-900); }
    .page-sub { font-size: 14px; color: var(--gray-500); margin-top: 3px; }

    /* Stat Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: 14px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .stat-card .sc-label { font-size: 13px; color: var(--gray-500); margin-bottom: 8px; }
    .stat-card .sc-val { font-size: 30px; font-weight: 800; color: var(--gray-900); line-height: 1; }
    .stat-card .sc-sub { font-size: 12px; color: var(--gray-400); margin-top: 8px; display: flex; align-items: center; gap: 4px; }
    .stat-card .sc-sub.up { color: #16a34a; }
    .stat-card .sc-sub.warn { color: #d97706; }
    .stat-card .sc-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .icon-blue   { background: #eff6ff; color: #3b82f6; }
    .icon-green  { background: #f0fdf4; color: #16a34a; }
    .icon-orange { background: #fff7ed; color: #d97706; }
    .icon-purple { background: #faf5ff; color: #9333ea; }

    /* Charts row */
    .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }

    /* Bottom grid */
    .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    /* Pegawai Dashboard */
    .checkin-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 24px; margin-bottom: 24px; }
    .checkin-header { font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .time-boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
    .time-box { border: 1.5px solid var(--gray-200); border-radius: 10px; padding: 16px; text-align: center; }
    .time-box .tb-label { font-size: 12px; color: var(--gray-400); margin-bottom: 6px; }
    .time-box .tb-val { font-size: 32px; font-weight: 800; }
    .time-box.has-in .tb-val { color: #16a34a; }
    .time-box.has-out .tb-val { color: var(--primary); }
    .time-box .tb-val.empty { color: var(--gray-300, #d1d5db); }

    .rekap-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
    .rekap-box { background: #fff; border: 1px solid var(--gray-200); border-radius: 12px; padding: 16px; text-align: center; }
    .rekap-box .rb-val { font-size: 28px; font-weight: 800; }
    .rekap-box .rb-label { font-size: 12px; color: var(--gray-400); margin-top: 4px; }

    @media(max-width: 1100px) {
        .stat-grid { grid-template-columns: repeat(2, 1fr); }
        .chart-grid, .bottom-grid { grid-template-columns: 1fr; }
        .rekap-grid-4 { grid-template-columns: repeat(2, 1fr); }
    }
    @media(max-width: 640px) {
        .stat-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')
@php $user = auth()->user(); @endphp

@if($user->isAdmin())
{{-- ==================== ADMIN DASHBOARD ==================== --}}

<div class="page-header">
    <div class="page-title">Dashboard</div>
    <div class="page-sub">
        Selamat datang kembali! Berikut ringkasan data absensi RSUD hari ini,
        {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}.
    </div>
</div>

{{-- Stat Cards --}}
<div class="stat-grid">
    <a href="{{ route('pegawai.index') }}" class="stat-card" style="text-decoration:none;transition:transform .2s" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
        <div>
            <div class="sc-label">Total Pegawai</div>
            <div class="sc-val">{{ $data['total_pegawai'] }}</div>
            <div class="sc-sub up"><i class="bi bi-arrow-right-short"></i> Lihat semua pegawai</div>
        </div>
        <div class="sc-icon icon-blue"><i class="bi bi-people-fill"></i></div>
    </a>
    <div class="stat-card">
        <div>
            <div class="sc-label">Hadir Hari Ini</div>
            <div class="sc-val">{{ $data['hadir_hari_ini'] }}</div>
            <div class="sc-sub">
                {{ $data['total_pegawai'] > 0 ? round($data['hadir_hari_ini'] / $data['total_pegawai'] * 100) : 0 }}% kehadiran
            </div>
        </div>
        <div class="sc-icon icon-green"><i class="bi bi-patch-check-fill"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <div class="sc-label">Terlambat Hari Ini</div>
            <div class="sc-val">{{ $data['terlambat_hari_ini'] }}</div>
            <div class="sc-sub warn"><i class="bi bi-exclamation-circle"></i> Perlu perhatian</div>
        </div>
        <div class="sc-icon icon-orange"><i class="bi bi-clock-history"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <div class="sc-label">Izin Hari Ini</div>
            <div class="sc-val">{{ $data['izin_hari_ini'] }}</div>
            <div class="sc-sub warn">
                @if($data['izin_hari_ini'] > 0) <i class="bi bi-info-circle"></i> Sedang berlangsung
                @else Tidak ada izin
                @endif
            </div>
        </div>
        <div class="sc-icon icon-purple"><i class="bi bi-calendar-x-fill"></i></div>
    </div>
</div>

<div class="stat-grid" style="margin-top:-12px;margin-bottom:24px">
    <div class="stat-card" style="box-shadow:none;border:1px dashed var(--gray-200)">
        <div>
            <div class="sc-label" style="font-size:11px">Pulang Awal (PSW)</div>
            <div class="sc-val" style="font-size:20px;color:#d97706">{{ $data['psw_hari_ini'] }}</div>
            <div style="font-size:10px;color:var(--gray-400)">Hari ini</div>
        </div>
        <div class="sc-icon" style="background:#fff7ed;color:#d97706;width:32px;height:32px;font-size:14px"><i class="bi bi-box-arrow-left"></i></div>
    </div>
    <div class="stat-card" style="box-shadow:none;border:1px dashed var(--gray-200)">
        <div>
            <div class="sc-label" style="font-size:11px">Lupa Absen Pulang</div>
            <div class="sc-val" style="font-size:20px;color:#dc2626">{{ $data['lupa_absen_hari_ini'] }}</div>
            <div style="font-size:10px;color:var(--gray-400)">Akumulasi</div>
        </div>
        <div class="sc-icon" style="background:#fef2f2;color:#dc2626;width:32px;height:32px;font-size:14px"><i class="bi bi-question-circle"></i></div>
    </div>
    <div class="stat-card" style="box-shadow:none;border:1px dashed var(--gray-200)">
        <div>
            <div class="sc-label" style="font-size:11px">Alpha Hari Ini</div>
            <div class="sc-val" style="font-size:20px;color:#991b1b">{{ $data['alpha_hari_ini'] }}</div>
            <div style="font-size:10px;color:var(--gray-400)">Belum Check-in</div>
        </div>
        <div class="sc-icon" style="background:#fef2f2;color:#991b1b;width:32px;height:32px;font-size:14px"><i class="bi bi-person-x"></i></div>
    </div>
    <div class="stat-card" style="box-shadow:none;border:1px dashed var(--gray-200)">
        <div>
            <div class="sc-label" style="font-size:11px">Koreksi Pending</div>
            <div class="sc-val" style="font-size:20px;color:var(--primary)">{{ \App\Models\KoreksiAbsensi::where('status', 'pending')->count() }}</div>
            <div style="font-size:10px;color:var(--gray-400)">Perlu Verifikasi</div>
        </div>
        <div class="sc-icon" style="background:var(--primary-light);color:var(--primary);width:32px;height:32px;font-size:14px"><i class="bi bi-pencil-square"></i></div>
    </div>
</div>

{{-- Charts --}}
<div class="chart-grid">
    <div class="card">
        <div class="card-header">
            Trend Kehadiran (14 Hari)
            <span style="font-size:12px;color:var(--gray-400);font-weight:400">Real-time</span>
        </div>
        <div class="card-body">
            <canvas id="chartKehadiran" height="220"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Rekap Absensi Bulan Ini
            <span style="font-size:12px;color:var(--gray-400);font-weight:400">
                {{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM Y') }}
            </span>
        </div>
        <div class="card-body">
            <canvas id="chartRekap" height="220"></canvas>
        </div>
    </div>
</div>

{{-- Unit Analytics --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span><i class="bi bi-building me-2" style="color:var(--primary)"></i>Persentase Kehadiran per Unit (Bulan Ini)</span>
        <span style="font-size:12px;color:var(--gray-400);font-weight:400">Berdasarkan rasio hari kerja</span>
    </div>
    <div class="card-body">
        <div style="height:320px">
            <canvas id="chartUnit"></canvas>
        </div>
    </div>
</div>

{{-- Bottom --}}
<div class="bottom-grid">
    {{-- Absensi Terkini --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-clock me-2" style="color:var(--primary)"></i>Absensi Terkini Hari Ini</span>
            <a href="{{ route('absensi.index') }}" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Pegawai</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['absensi_terkini'] as $a)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                                    {{ strtoupper(substr($a->user->name ?? 'P', 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px">{{ $a->user->name ?? '-' }}</div>
                                    <div style="font-size:11px;color:var(--gray-400)">{{ $a->user->unit ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight:600;color:#16a34a">
                            {{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '-' }}
                        </td>
                        <td style="color:var(--primary)">
                            {{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '-' }}
                            @if($a->is_psw)
                                <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 4px;border-radius:4px;font-weight:700;margin-left:4px">PSW</span>
                            @endif
                        </td>
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
                        <td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">
                            <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;opacity:.4"></i>
                            Belum ada absensi hari ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Status Kehadiran --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-bar-chart me-2" style="color:var(--primary)"></i>Status Kehadiran Bulan Ini</span>
        </div>
        <div class="card-body">
            @php
                $rekap = $data['rekap_bulan'];
                $totalRekap = $rekap->sum();
                $items = [
                    'hadir'     => ['label' => 'Hadir',     'color' => '#16a34a'],
                    'terlambat' => ['label' => 'Terlambat', 'color' => '#d97706'],
                    'izin'      => ['label' => 'Izin',      'color' => '#2563eb'],
                    'sakit'     => ['label' => 'Sakit',     'color' => '#9333ea'],
                    'alpha'     => ['label' => 'Alpha',     'color' => '#dc2626'],
                ];
            @endphp
            @foreach($items as $key => $item)
            <div style="margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span style="font-size:13px;color:var(--gray-700)">{{ $item['label'] }}</span>
                    <span style="font-size:13px;font-weight:700;color:{{ $item['color'] }}">{{ $rekap[$key] ?? 0 }}</span>
                </div>
                <div style="height:6px;background:var(--gray-100);border-radius:99px;overflow:hidden">
                    <div style="height:100%;width:{{ $totalRekap > 0 ? round(($rekap[$key] ?? 0) / $totalRekap * 100) : 0 }}%;background:{{ $item['color'] }};border-radius:99px;transition:width .4s"></div>
                </div>
            </div>
            @endforeach
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--gray-100);display:flex;justify-content:space-between;font-size:13px;color:var(--gray-500)">
                <span>Total record</span>
                <span style="font-weight:700;color:var(--gray-900)">{{ $totalRekap }}</span>
            </div>
        </div>
    </div>
    {{-- Unit Performa Terbaik --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-trophy me-2" style="color:#d97706"></i>Peringkat Kedisiplinan Unit</span>
        </div>
        <div class="card-body" style="padding:0">
            @foreach($data['stats_unit']->take(5) as $idx => $s)
            <div style="padding:16px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:{{ $loop->last ? 'none' : '1px solid var(--gray-50)' }}">
                <div style="display:flex;align-items:center;gap:15px">
                    <div style="width:28px;height:28px;border-radius:50%;background:{{ $idx == 0 ? '#fef3c7' : ($idx == 1 ? '#f1f5f9' : ($idx == 2 ? '#ffedd5' : 'var(--gray-50)')) }};color:{{ $idx == 0 ? '#92400e' : ($idx == 1 ? '#475569' : ($idx == 2 ? '#9a3412' : 'var(--gray-500)')) }};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px">
                        {{ $idx+1 }}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:var(--gray-900)">{{ $s['unit'] }}</div>
                        <div style="font-size:11px;color:var(--gray-400)">{{ $s['total'] }} Record Kehadiran</div>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:800;font-size:16px;color:{{ $s['presentase'] >= 90 ? '#16a34a' : ($s['presentase'] >= 75 ? '#d97706' : '#dc2626') }}">{{ $s['presentase'] }}%</div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;font-weight:700;color:var(--gray-400)">Skor Unit</div>
                </div>
            </div>
            @endforeach
            <div style="padding:16px;text-align:center;background:var(--gray-50)">
                <a href="{{ route('laporan.rekap') }}" style="font-size:12px;font-weight:700;color:var(--primary);text-decoration:none">Lihat Semua Laporan Unit <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </div>
</div>

@else
{{-- ==================== PEGAWAI DASHBOARD ==================== --}}

@php
    $absensiHariIni = $data['absensi_hari_ini'];
    $shiftHariIni   = $data['shift_hari_ini'];
    $liburHariIni   = $data['libur_hari_ini'];
    $rekap          = $data['rekap_bulan'];
    $hariIni        = \Carbon\Carbon::today()->dayOfWeek;
    $isWeekend      = in_array($hariIni, config('attendance.hari_libur', [0,6]));
@endphp

<div class="page-header">
    <div class="page-title">Dashboard</div>
    <div class="page-sub">
        Selamat datang, <strong>{{ $user->name }}</strong>! —
        {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
    </div>
</div>

{{-- Rekap bulan --}}
<div class="rekap-grid-4">
    <div class="rekap-box">
        <div class="rb-val" style="color:#16a34a">{{ $rekap['hadir'] }}</div>
        <div class="rb-label">Hadir</div>
    </div>
    <div class="rekap-box">
        <div class="rb-val" style="color:#d97706">{{ $rekap['terlambat'] }}</div>
        <div class="rb-label">Terlambat</div>
    </div>
    <div class="rekap-box">
        <div class="rb-val" style="color:#2563eb">{{ $rekap['izin'] }}</div>
        <div class="rb-label">Izin</div>
    </div>
    <div class="rekap-box">
        <div class="rb-val" style="color:#dc2626">{{ $rekap['alpha'] }}</div>
        <div class="rb-label">Alpha</div>
    </div>
</div>

{{-- Absensi Hari Ini --}}
<div class="checkin-card">
    <div class="checkin-header">
        <i class="bi bi-fingerprint" style="color:var(--primary);font-size:20px"></i>
        Absensi Hari Ini
        @if($absensiHariIni)
        <span class="badge badge-{{ $absensiHariIni->status }}" style="margin-left:auto">
            {{ ucfirst($absensiHariIni->status) }}
        </span>
        @endif
    </div>

    @if($liburHariIni)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
        <i class="bi bi-calendar-x-fill" style="color:#dc2626"></i>
        <div style="font-size:13px;font-weight:600;color:#dc2626">Libur Nasional: {{ $liburHariIni->nama_libur }}</div>
    </div>
    @elseif($user->jenis_absensi === 'normal' && $isWeekend)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
        <i class="bi bi-house-heart-fill" style="color:#dc2626"></i>
        <div style="font-size:13px;font-weight:600;color:#dc2626">Hari libur mingguan. Nikmati istirahat Anda!</div>
    </div>
    @elseif($user->jenis_absensi === 'normal')
    @php $jamKantor = config('attendance.jam_kantor')[$hariIni] ?? null; @endphp
    @if($jamKantor)
    <div style="background:var(--primary-light);border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
        <i class="bi bi-building" style="color:var(--primary)"></i>
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--primary)">Jam Kantor Hari Ini</div>
            <div style="font-size:12px;color:var(--gray-500)">{{ $jamKantor['masuk'] }} — {{ $jamKantor['keluar'] }}</div>
        </div>
    </div>
    @endif
    @elseif($shiftHariIni)
    <div style="background:var(--primary-light);border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
        <i class="bi bi-clock" style="color:var(--primary)"></i>
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--primary)">
                Shift {{ ucfirst($shiftHariIni->jenis_shift) }}
            </div>
            <div style="font-size:12px;color:var(--gray-500)">
                {{ $shiftHariIni->jam_masuk }} — {{ $shiftHariIni->jam_keluar }}
            </div>
        </div>
    </div>
    @endif

    <div class="time-boxes">
        <div class="time-box {{ $absensiHariIni?->check_in ? 'has-in' : '' }}">
            <div class="tb-label">Check In</div>
            <div class="tb-val {{ $absensiHariIni?->check_in ? '' : 'empty' }}">
                {{ $absensiHariIni?->check_in ? \Carbon\Carbon::parse($absensiHariIni->check_in)->format('H:i') : '--:--' }}
            </div>
        </div>
        <div class="time-box {{ $absensiHariIni?->check_out ? 'has-out' : '' }}">
            <div class="tb-label">Check Out</div>
            <div class="tb-val {{ $absensiHariIni?->check_out ? '' : 'empty' }}">
                {{ $absensiHariIni?->check_out ? \Carbon\Carbon::parse($absensiHariIni->check_out)->format('H:i') : '--:--' }}
            </div>
        </div>
    </div>

    @if(!$absensiHariIni?->check_in || !$absensiHariIni?->check_out)
    <a href="{{ route('absensi.index') }}" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
        <i class="bi bi-fingerprint"></i>
        {{ !$absensiHariIni?->check_in ? 'Absen Sekarang' : 'Check Out' }}
    </a>
    @else
    <div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:10px;color:#16a34a;font-weight:600;font-size:14px;display:flex;align-items:center;justify-content:center;gap:8px">
        <i class="bi bi-check-circle-fill"></i> Absensi hari ini selesai!
    </div>
    @endif
</div>

{{-- Riwayat --}}
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-list-check me-2" style="color:var(--primary)"></i>Riwayat 5 Hari Terakhir</span>
        <a href="{{ route('absensi.index') }}" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['absensi_bulan'] as $a)
                <tr>
                    <td style="font-weight:600">
                        {{ \Carbon\Carbon::parse($a->tanggal)->locale('id')->isoFormat('ddd, D MMM Y') }}
                    </td>
                    <td style="color:#16a34a;font-weight:600">
                        {{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '-' }}
                    </td>
                    <td style="color:var(--primary)">
                        {{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '-' }}
                    </td>
                    <td><span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">
                        Belum ada data absensi bulan ini
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endif
@endsection

@push('scripts')
@if(auth()->user()->isAdmin())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.color = '#6b7280';

// ---- Chart 1: Trend Kehadiran 14 hari ----
@php
$labels14 = [];
$hadirData = [];
$terlambatData = [];
for ($i = 13; $i >= 0; $i--) {
    $tgl = \Carbon\Carbon::now()->subDays($i);
    $labels14[] = $tgl->format('d M');
    $hadirData[] = \App\Models\Absensi::whereDate('tanggal', $tgl->toDateString())
        ->where('status', 'hadir')->count();
    $terlambatData[] = \App\Models\Absensi::whereDate('tanggal', $tgl->toDateString())
        ->where('status', 'terlambat')->count();
}
@endphp

new Chart(document.getElementById('chartKehadiran'), {
    type: 'line',
    data: {
        labels: @json($labels14),
        datasets: [
            {
                label: 'Hadir',
                data: @json($hadirData),
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,.08)',
                tension: .4,
                fill: true,
                pointBackgroundColor: '#16a34a',
                pointRadius: 4,
                pointHoverRadius: 6,
            },
            {
                label: 'Terlambat',
                data: @json($terlambatData),
                borderColor: '#d97706',
                backgroundColor: 'rgba(217,119,6,.08)',
                tension: .4,
                fill: true,
                pointBackgroundColor: '#d97706',
                pointRadius: 4,
                pointHoverRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } }
        }
    }
});

// ---- Chart 2: Rekap status bulan ini ----
@php
$rekapBulan = $data['rekap_bulan'];
@endphp
new Chart(document.getElementById('chartRekap'), {
    type: 'doughnut',
    data: {
        labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha'],
        datasets: [{
            data: [
                {{ $rekapBulan['hadir'] ?? 0 }},
                {{ $rekapBulan['terlambat'] ?? 0 }},
                {{ $rekapBulan['izin'] ?? 0 }},
                {{ $rekapBulan['sakit'] ?? 0 }},
                {{ $rekapBulan['alpha'] ?? 0 }},
            ],
            backgroundColor: ['#16a34a','#d97706','#2563eb','#9333ea','#dc2626'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 12 } } }
        }
    }
});

// ---- Chart 3: Kehadiran per Unit ----
new Chart(document.getElementById('chartUnit'), {
    type: 'bar',
    data: {
        labels: @json($data['stats_unit']->pluck('unit')),
        datasets: [{
            label: 'Persentase Kehadiran (%)',
            data: @json($data['stats_unit']->pluck('presentase')),
            backgroundColor: @json($data['stats_unit']->map(fn($s) => $s['presentase'] >= 90 ? '#16a34a' : ($s['presentase'] >= 75 ? '#d97706' : '#3b82f6'))),
            borderRadius: 8,
            maxBarThickness: 40,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) { return context.parsed.y + '% Kehadiran'; }
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
            y: { beginAtZero: true, max: 100, ticks: { stepSize: 20, font: { size: 11 } }, grid: { borderDash: [5, 5] } }
        }
    }
});
</script>
@endif
@endpush
