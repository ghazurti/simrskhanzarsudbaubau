@extends('layouts.app')
@section('title', 'Rekapitulasi Bulanan')
@section('breadcrumb')Laporan / <span>Rekapitulasi</span>@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
        <div style="font-size:22px;font-weight:800;color:var(--gray-900)">Rekapitulasi Absensi</div>
        <div style="font-size:13px;color:var(--gray-400);margin-top:3px">
            Ringkasan kehadiran kseluruhan pegawai periode {{ \Carbon\Carbon::create($tahun, $bulan)->locale('id')->isoFormat('MMMM YYYY') }}
        </div>
    </div>
    <a href="{{ route('laporan.export_rekap', request()->query()) }}" class="btn btn-primary">
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
            <button type="submit" class="btn btn-primary" style="padding:10px 20px">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span><i class="bi bi-clipboard-data me-2" style="color:var(--primary)"></i>Ringkasan Kehadiran</span>
    </div>
    <div style="overflow-x:auto">
        <table class="table-hover">
            <thead>
                <tr>
                    <th rowspan="2" style="text-align:center; vertical-align:middle">No</th>
                    <th rowspan="2" style="vertical-align:middle">Nama Pegawai</th>
                    <th rowspan="2" style="vertical-align:middle">Unit/Bagian</th>
                    <th colspan="5" style="text-align:center; background:var(--gray-50)">Status Kehadiran</th>
                    <th rowspan="2" style="text-align:center; vertical-align:middle">Lembur</th>
                </tr>
                <tr style="font-size:11px; background:var(--gray-50)">
                    <th style="text-align:center; color:#16a34a">Hadir</th>
                    <th style="text-align:center; color:#d97706">Telat</th>
                    <th style="text-align:center; color:#2563eb">Izin</th>
                    <th style="text-align:center; color:#9333ea">Sakit</th>
                    <th style="text-align:center; color:#dc2626">Alpha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $i => $u)
                @php
                    $stats = $u->absensis->groupBy('status');
                    $hadir = ($stats['hadir'] ?? collect())->count();
                    $telat = ($stats['terlambat'] ?? collect())->count();
                    $izin = ($stats['izin'] ?? collect())->count();
                    $sakit = ($stats['sakit'] ?? collect())->count();
                    $alpha = ($stats['alpha'] ?? collect())->count();
                    
                    $totalLembur = \App\Models\Lembur::where('user_id', $u->id)
                        ->whereMonth('tanggal', $bulan)
                        ->whereYear('tanggal', $tahun)
                        ->where('status', 'approved')
                        ->count();
                @endphp
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:600">{{ $u->name }}</div>
                        <div style="font-size:11px; color:var(--gray-400)">{{ $u->nip ?? '-' }}</div>
                    </td>
                    <td>
                        @if($u->unit)
                        <span style="font-size:12px; background:#f3f4f6; padding:2px 8px; border-radius:12px">{{ $u->unit }}</span>
                        @else - @endif
                    </td>
                    <td style="text-align:center; font-weight:700; color:#16a34a">{{ $hadir }}</td>
                    <td style="text-align:center; font-weight:700; color:#d97706">{{ $telat }}</td>
                    <td style="text-align:center; font-weight:700; color:#2563eb">{{ $izin }}</td>
                    <td style="text-align:center; font-weight:700; color:#9333ea">{{ $sakit }}</td>
                    <td style="text-align:center; font-weight:700; color:#dc2626">{{ $alpha }}</td>
                    <td style="text-align:center">
                        <span class="badge" style="background:var(--primary-light); color:var(--primary)">
                            {{ $totalLembur }}x
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
    .table-hover tbody tr:hover { background-color: var(--gray-50); }
    th { border: 1px solid var(--gray-200) !important; }
    td { border: 1px solid var(--gray-100) !important; }
</style>
@endsection
