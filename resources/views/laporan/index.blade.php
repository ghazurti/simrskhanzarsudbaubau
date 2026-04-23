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
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
        <div style="font-size:22px;font-weight:800;color:var(--gray-900)">Laporan Absensi</div>
        <div style="font-size:13px;color:var(--gray-400);margin-top:3px">
            {{ \Carbon\Carbon::create($tahun, $bulan)->locale('id')->isoFormat('MMMM YYYY') }}
        </div>
    </div>
    <a href="{{ route('laporan.export', request()->query()) }}" class="btn btn-primary">
        <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
    </a>
</div>

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
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:12px">Pegawai</label>
                <select name="user_id" class="form-control form-select">
                    <option value="">Semua Pegawai</option>
                    @foreach($pegawais as $p)
                    <option value="{{ $p->id }}" {{ $userId==$p->id?'selected':'' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding:10px 20px">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request()->hasAny(['bulan','tahun','user_id']))
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
                    <td style="color:var(--gray-400);font-size:12px">{{ $dur ?? '-' }}</td>
                    <td><span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:48px;color:var(--gray-400)">
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
@endsection
