@extends('layouts.app')
@section('title', 'Skor Kehadiran')
@section('breadcrumb')Laporan / <a href="{{ route('laporan.index') }}" style="color:inherit">Absensi</a> / <span>Skor Kehadiran</span>@endsection

@push('styles')
<style>
    .skor-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
    @media(max-width:960px){ .skor-grid { grid-template-columns:1fr; } }

    /* Skor meter */
    .skor-meter { text-align: center; padding: 24px; }
    .skor-circle {
        width: 140px; height: 140px;
        border-radius: 50%;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        margin: 0 auto 16px;
        border: 8px solid;
        transition: border-color .3s;
    }
    .skor-circle .val { font-size: 36px; font-weight: 800; line-height: 1; }
    .skor-circle .lbl { font-size: 12px; margin-top: 4px; }

    .skor-excellent { border-color: #16a34a; color: #16a34a; }
    .skor-good      { border-color: #2563eb; color: #2563eb; }
    .skor-warning   { border-color: #d97706; color: #d97706; }
    .skor-danger    { border-color: #dc2626; color: #dc2626; }

    /* Tabel kriteria */
    .kriteria-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .kriteria-table th {
        background: #f8fafc;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        font-weight: 600;
        font-size: 12px;
        color: #374151;
        text-align: center;
    }
    .kriteria-table td {
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    .kriteria-table .kode-cell {
        font-weight: 700;
        color: var(--primary);
        text-align: center;
        white-space: nowrap;
    }
    .kriteria-table .num-cell { text-align: center; font-weight: 600; }
    .kriteria-table .persen-cell { text-align: center; color: #d97706; font-weight: 600; }
    .kriteria-table .jumlah-cell {
        text-align: center;
        font-weight: 700;
    }
    .kriteria-table .jumlah-cell.has-val { color: #dc2626; }
    .kriteria-table tfoot td {
        background: #1e3a5f;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        text-align: center;
        padding: 12px 14px;
    }
    .kriteria-table .indikator-cell {
        font-weight: 600;
        background: #fafafa;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
        <div style="font-size:22px;font-weight:800;color:var(--gray-900)">Skor Kehadiran Pegawai</div>
        <div style="font-size:13px;color:var(--gray-400);margin-top:3px">Lembar Perhitungan Skor Kehadiran — Dinas Kesehatan</div>
    </div>
    <div style="display:flex;gap:8px">
        @if($pegawai && $skor)
        <a href="{{ route('skor.cetak', ['user_id'=>$pegawai->id,'bulan'=>$bulan,'tahun'=>$tahun]) }}"
            target="_blank" class="btn btn-primary">
            <i class="bi bi-printer"></i> Cetak / PDF
        </a>
        @endif
        <a href="{{ route('skor.export', ['bulan'=>$bulan,'tahun'=>$tahun,'unit'=>$unit]) }}"
            class="btn" style="background:#16a34a;color:#fff;display:flex;align-items:center;gap:6px">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
    </div>
</div>

{{-- Filter --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <form style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div>
                <label class="form-label" style="font-size:12px">Unit</label>
                <select name="unit" class="form-control form-select" style="min-width:180px">
                    <option value="">-- Semua Unit --</option>
                    @foreach($units as $u)
                    <option value="{{ $u }}" {{ $unit == $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px">Pegawai</label>
                <select name="user_id" class="form-control form-select" style="min-width:220px">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach($pegawais as $p)
                    <option value="{{ $p->id }}" {{ $pegawai?->id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} {{ $p->nip ? '('.$p->nip.')' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
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
                <i class="bi bi-calculator"></i> Hitung Skor
            </button>
        </form>
    </div>
</div>

@if($pegawai && $skor)
<div class="skor-grid">

    {{-- Kiri: Profil + Skor --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Info Pegawai --}}
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-person-badge me-2" style="color:var(--primary)"></i>Pegawai Yang Dinilai</span>
            </div>
            <div class="card-body" style="padding:0">
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    @foreach([
                        ['No','Uraian','Data'],
                        [1,'Nama',$pegawai->name],
                        [2,'NIP',$pegawai->nip ?? '-'],
                        [3,'Status',$pegawai->pangkat_gol ?? '-'],
                        [4,'Jabatan',$pegawai->jabatan ?? '-'],
                        [5,'Unit',$pegawai->unit ?? '-'],
                    ] as $i => $row)
                    @if($i === 0)
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e5e7eb">
                            @foreach($row as $h)
                            <th style="padding:9px 14px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @else
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:9px 14px;color:#9ca3af;width:28px">{{ $row[0] }}</td>
                        <td style="padding:9px 14px;color:#6b7280;font-weight:600">{{ $row[1] }}</td>
                        <td style="padding:9px 14px;color:#111827;font-weight:500">{{ $row[2] }}</td>
                    </tr>
                    @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Skor Circle --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-award me-2" style="color:var(--primary)"></i>Hasil Skor</div>
            <div class="card-body skor-meter">
                @php
                    $s = $skor['skor_akhir'];
                    $cls = $s >= 90 ? 'skor-excellent' : ($s >= 75 ? 'skor-good' : ($s >= 60 ? 'skor-warning' : 'skor-danger'));
                    $label = $s >= 90 ? 'Sangat Baik' : ($s >= 75 ? 'Baik' : ($s >= 60 ? 'Cukup' : 'Kurang'));
                @endphp
                <div class="skor-circle {{ $cls }}">
                    <div class="val">{{ number_format($s, 2) }}</div>
                    <div class="lbl">/ 100</div>
                </div>
                <div style="font-size:16px;font-weight:700;margin-bottom:16px">{{ $label }}</div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center">
                    <div style="background:#f0fdf4;border-radius:8px;padding:10px">
                        <div style="font-size:20px;font-weight:800;color:#16a34a">{{ $skor['total_hadir'] }}</div>
                        <div style="font-size:11px;color:#6b7280">Hadir</div>
                    </div>
                    <div style="background:#fef2f2;border-radius:8px;padding:10px">
                        <div style="font-size:20px;font-weight:800;color:#dc2626">{{ $skor['total_alpha'] }}</div>
                        <div style="font-size:11px;color:#6b7280">Alpha</div>
                    </div>
                    <div style="background:#eff6ff;border-radius:8px;padding:10px">
                        <div style="font-size:20px;font-weight:800;color:#2563eb">{{ $skor['total_izin'] }}</div>
                        <div style="font-size:11px;color:#6b7280">Izin</div>
                    </div>
                </div>

                <div style="margin-top:16px;padding:12px;background:#f8fafc;border-radius:10px;text-align:left">
                    <div style="font-size:12px;color:#6b7280;margin-bottom:6px">Rincian Potongan</div>
                    <div style="display:flex;justify-content:space-between;font-size:13px">
                        <span>Skor Awal</span>
                        <span style="font-weight:700">100.00</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:#dc2626">
                        <span>Total Potongan</span>
                        <span style="font-weight:700">- {{ number_format($skor['total_potongan'], 2) }}</span>
                    </div>
                    <div style="border-top:1px solid #e5e7eb;margin:8px 0;padding-top:8px;display:flex;justify-content:space-between;font-size:14px;font-weight:700">
                        <span>Skor Akhir</span>
                        <span style="color:{{ $s >= 90 ? '#16a34a' : ($s >= 75 ? '#2563eb' : ($s >= 60 ? '#d97706' : '#dc2626')) }}">
                            {{ number_format($s, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Kanan: Tabel Kriteria --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-table me-2" style="color:var(--primary)"></i>Detail Perhitungan Skor</span>
            <span style="font-size:12px;color:var(--gray-400);font-weight:400">
                Periode: {{ \Carbon\Carbon::create($tahun,$bulan)->locale('id')->isoFormat('MMMM YYYY') }}
            </span>
        </div>
        <div style="overflow-x:auto">
            <table class="kriteria-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:30px">No</th>
                        <th rowspan="2">Indikator</th>
                        <th rowspan="2" style="width:50px">Kode</th>
                        <th rowspan="2">Kriteria</th>
                        <th rowspan="2" style="width:50px">%</th>
                        <th colspan="3">Hasil Pengukuran</th>
                    </tr>
                    <tr>
                        <th style="width:70px">Kali<br><small>TL</small></th>
                        <th style="width:70px">Kali<br><small>PSW</small></th>
                        <th style="width:80px">Jumlah</th>
                    </tr>
                    <tr>
                        <th colspan="5" style="background:#1e3a5f;color:#fff;border-color:#1e3a5f"></th>
                        <th colspan="2" style="background:#1e3a5f;color:#fff;border-color:#1e3a5f;font-size:11px">TL / PSW</th>
                        <th style="background:#1e3a5f;color:#fff;border-color:#1e3a5f">100</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="num-cell">1</td>
                        <td class="indikator-cell" rowspan="6">Ketidakhadiran</td>
                        <td class="kode-cell">KT1</td>
                        <td>{{ $skor['detail']['KT1']['label'] }}</td>
                        <td class="persen-cell">{{ $skor['detail']['KT1']['persen'] }}</td>
                        <td class="num-cell">
                            {{ collect($skor['hari'])->where('tl', '>', 0)->where('tl', '<=', 30)->count() }}
                        </td>
                        <td class="num-cell">
                            {{ collect($skor['hari'])->where('psw', '>', 0)->where('psw', '<=', 30)->count() }}
                        </td>
                        <td class="jumlah-cell {{ $skor['detail']['KT1']['jumlah'] > 0 ? 'has-val' : '' }}">
                            {{ $skor['detail']['KT1']['jumlah'] > 0 ? number_format($skor['detail']['KT1']['jumlah'],2) : '0' }}
                        </td>
                    </tr>
                    @foreach(['KT2','KT3','KT4','KT5','KT6'] as $kode)
                    <tr>
                        <td class="num-cell"></td>
                        <td class="kode-cell">{{ $kode }}</td>
                        <td>{{ $skor['detail'][$kode]['label'] }}</td>
                        <td class="persen-cell">{{ $skor['detail'][$kode]['persen'] }}</td>
                        <td class="num-cell">
                            @if($kode == 'KT2') {{ collect($skor['hari'])->where('tl', '>', 30)->where('tl', '<=', 60)->count() }}
                            @elseif($kode == 'KT3') {{ collect($skor['hari'])->where('tl', '>', 60)->where('tl', '<=', 90)->count() }}
                            @elseif($kode == 'KT4') {{ collect($skor['hari'])->where('tl', '>', 90)->count() }}
                            @elseif($kode == 'KT5' || $kode == 'KT6') {{ $skor['detail'][$kode]['kali'] }}
                            @else - @endif
                        </td>
                        <td class="num-cell">
                            @if($kode == 'KT2') {{ collect($skor['hari'])->where('psw', '>', 30)->where('psw', '<=', 60)->count() }}
                            @elseif($kode == 'KT3') {{ collect($skor['hari'])->where('psw', '>', 60)->where('psw', '<=', 90)->count() }}
                            @elseif($kode == 'KT4') {{ collect($skor['hari'])->where('psw', '>', 90)->count() }}
                            @else 0 @endif
                        </td>
                        <td class="jumlah-cell {{ $skor['detail'][$kode]['jumlah'] > 0 ? 'has-val' : '' }}">
                            {{ $skor['detail'][$kode]['jumlah'] > 0 ? number_format($skor['detail'][$kode]['jumlah'],2) : '0' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align:right;font-size:14px">Total Skor</td>
                        <td style="font-size:18px;color:#fbbf24">{{ number_format($skor['skor_akhir'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Keterangan --}}
        <div style="padding:16px 20px;border-top:1px solid #f3f4f6">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px">Keterangan Penilaian:</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                @foreach([
                    ['≥ 90','Sangat Baik','#16a34a','#f0fdf4'],
                    ['75 - 89','Baik','#2563eb','#eff6ff'],
                    ['60 - 74','Cukup','#d97706','#fff7ed'],
                    ['< 60','Kurang','#dc2626','#fef2f2'],
                ] as $k)
                <div style="display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:20px;background:{{ $k[3] }};font-size:12px">
                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $k[2] }};display:inline-block"></span>
                    <span style="font-weight:700;color:{{ $k[2] }}">{{ $k[0] }}</span>
                    <span style="color:#6b7280">= {{ $k[1] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-body" style="text-align:center;padding:60px">
        <i class="bi bi-calculator" style="font-size:48px;color:#d1d5db;display:block;margin-bottom:16px"></i>
        <div style="font-size:16px;font-weight:600;color:#374151;margin-bottom:8px">Pilih Pegawai & Periode</div>
        <div style="font-size:13px;color:#9ca3af">Gunakan filter di atas untuk menghitung skor kehadiran pegawai</div>
    </div>
</div>
@endif
@endsection
