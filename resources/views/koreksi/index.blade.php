@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Persetujuan Koreksi' : 'Koreksi Absensi')
@section('breadcrumb')Kehadiran / <span>Koreksi</span>@endsection

@section('content')
<div style="font-size:22px;font-weight:800;color:var(--gray-900);margin-bottom:4px">
    {{ auth()->user()->isAdmin() ? 'Persetujuan Koreksi Absensi' : 'Pengajuan Koreksi Absensi' }}
</div>
<div style="font-size:13px;color:var(--gray-400);margin-bottom:24px">
    {{ auth()->user()->isAdmin() ? 'Kelola pengajuan perbaikan data absensi dari pegawai.' : 'Ajukan perbaikan jika Anda lupa absen atau ada kendala teknis.' }}
</div>

<div style="display:grid;grid-template-columns: {{ auth()->user()->isAdmin() ? '1fr' : '340px 1fr' }}; gap:20px; align-items: start;">
    
    @if(!auth()->user()->isAdmin())
    {{-- Form Pengajuan (Hanya Pegawai) --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-plus-circle me-2" style="color:var(--primary)"></i>Form Koreksi</span>
        </div>
        <div class="card-body">
            @php
                $batasJam = config('attendance.koreksi_batas_jam', '22:00');
                $lewatBatas = \Carbon\Carbon::now()->format('H:i') > $batasJam;
            @endphp
            <div style="background:{{ $lewatBatas ? '#fef2f2' : '#eff6ff' }};border:1px solid {{ $lewatBatas ? '#fecaca' : '#bfdbfe' }};border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:12px;color:{{ $lewatBatas ? '#dc2626' : '#1d4ed8' }};line-height:1.4">
                <i class="bi bi-clock-history me-1"></i>
                @if($lewatBatas)
                    Waktu pengajuan hari ini sudah ditutup (batas pukul <strong>{{ $batasJam }} WITA</strong>). Silakan ajukan kembali besok.
                @else
                    Batas waktu pengajuan koreksi setiap hari sampai pukul <strong>{{ $batasJam }} WITA</strong>.
                @endif
            </div>
            <form action="{{ route('koreksi.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Tanggal yang Diperbaiki</label>
                    <input type="date" name="tanggal" class="form-control" max="{{ date('Y-m-d') }}" value="{{ old('tanggal') }}" required>
                </div>
                <div style="display:grid;grid-template-columns: 1fr 1fr; gap:12px">
                    <div class="form-group">
                        <label class="form-label">Jam Masuk (Baru)</label>
                        <input type="time" name="jam_masuk" class="form-control" value="{{ old('jam_masuk') }}">
                        <div style="font-size:10px; color:var(--gray-400); margin-top:4px">Kosongkan jika tidak ada</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jam Keluar (Baru)</label>
                        <input type="time" name="jam_keluar" class="form-control" value="{{ old('jam_keluar') }}">
                        <div style="font-size:10px; color:var(--gray-400); margin-top:4px">Kosongkan jika tidak ada</div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Alasan Perbaikan</label>
                    <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Lupa absen karena mati lampu..." required>{{ old('alasan') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" {{ $lewatBatas ? 'disabled' : '' }} style="width:100%; justify-content:center; padding:12px">
                    <i class="bi bi-send"></i> Kirim Pengajuan
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Daftar Pengajuan --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-list-task me-2" style="color:var(--primary)"></i>Riwayat Pengajuan</span>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        @if(auth()->user()->isAdmin()) <th>Pegawai</th> @endif
                        <th>Koreksi Jam</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($koreksis as $k)
                    <tr>
                        <td style="font-weight:600; white-space:nowrap">
                            {{ $k->tanggal->locale('id')->isoFormat('ddd, D MMM Y') }}
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td>
                            <div style="font-weight:600">{{ $k->user->name }}</div>
                            <div style="font-size:11px; color:var(--gray-400)">{{ $k->user->unit ?? '-' }}</div>
                        </td>
                        @endif
                        <td style="white-space:nowrap">
                            <div><i class="bi bi-box-arrow-in-right me-1" style="color:#16a34a"></i> {{ $k->jam_masuk ? substr($k->jam_masuk,0,5) : '-' }}</div>
                            <div><i class="bi bi-box-arrow-right me-1" style="color:#2563eb"></i> {{ $k->jam_keluar ? substr($k->jam_keluar,0,5) : '-' }}</div>
                        </td>
                        <td>
                            <div style="font-size:13px; max-width:200px; line-height:1.4">{{ $k->alasan }}</div>
                            @if($k->status == 'rejected' && $k->catatan_admin)
                            <div style="font-size:11px; color:#dc2626; margin-top:4px; padding:4px 8px; background:#fef2f2; border-radius:4px">
                                <strong>Tolak:</strong> {{ $k->catatan_admin }}
                            </div>
                            @endif
                        </td>
                        <td>
                            @if($k->status == 'pending')
                                <span class="badge" style="background:#fff7ed; color:#c2410c"><i class="bi bi-clock me-1"></i>Menunggu</span>
                            @elseif($k->status == 'approved')
                                <span class="badge badge-hadir"><i class="bi bi-check-circle me-1"></i>Approved</span>
                            @else
                                <span class="badge badge-alpha"><i class="bi bi-x-circle me-1"></i>Ditolak</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:6px">
                                @if(auth()->user()->isAdmin() && $k->status == 'pending')
                                    <form action="{{ route('koreksi.approve', $k) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-icon" style="background:#dcfce7; color:#166534" title="Setujui">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-icon" style="background:#fef2f2; color:#dc2626" 
                                        onclick="openRejectModal({{ $k->id }})" title="Tolak">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif

                                @if(!auth()->user()->isAdmin() && $k->status == 'pending')
                                    <form action="{{ route('koreksi.destroy', $k) }}" method="POST" onsubmit="return confirm('Hapus pengajuan?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-icon btn-outline" style="color:#ef4444">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px; color:var(--gray-400)">
                            Tidak ada pengajuan koreksi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px">{{ $koreksis->links() }}</div>
    </div>
</div>

{{-- Modal Penolakan --}}
<div id="rejectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:999; align-items:center; justify-content:center;">
    <div class="card" style="width:400px; box-shadow:0 20px 25px -5px rgba(0,0,0,.1)">
        <div class="card-header">Tolak Koreksi Absensi</div>
        <div class="card-body">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="catatan_admin" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openRejectModal(id) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `/koreksi/${id}/reject`;
    modal.style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('rejectModal')) closeRejectModal();
}
</script>
@endpush
@endsection
