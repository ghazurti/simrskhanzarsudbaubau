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
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:48px;color:var(--gray-400)">
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
document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupFoto(); });
</script>
@endpush
