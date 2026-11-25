@extends('layouts.app')
@section('title', 'Manajemen Hari Libur')
@section('breadcrumb')Manajemen / <span>Hari Libur</span>@endsection

@section('content')
<div style="font-size:22px;font-weight:800;color:var(--gray-900);margin-bottom:4px">Manajemen Hari Libur</div>
<div style="font-size:13px;color:var(--gray-400);margin-bottom:24px">Atur daftar hari libur nasional (Tanggal Merah) untuk pengecualian jadwal shift.</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">
    
    {{-- Kolom Kiri: Sinkronisasi Otomatis + Form Tambah Manual --}}
    <div style="display:flex;flex-direction:column;gap:20px">
        {{-- Sinkronisasi Otomatis --}}
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-cloud-download me-2" style="color:var(--primary)"></i>Tarik Libur Otomatis</span>
            </div>
            <div class="card-body">
                <form action="{{ route('libur.sync') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Pilih Tahun</label>
                        <select name="tahun" class="form-control form-select" required>
                            @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
                        <input type="checkbox" id="sertakan_cuti_bersama" name="sertakan_cuti_bersama" value="1" checked style="width:16px;height:16px;cursor:pointer">
                        <label for="sertakan_cuti_bersama" style="margin:0;font-size:13px;cursor:pointer;color:var(--gray-700)">Sertakan Cuti Bersama</label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
                        <i class="bi bi-arrow-repeat"></i> Sinkronkan Libur Nasional
                    </button>
                    <div style="font-size:11px;color:var(--gray-400);margin-top:8px">
                        Sumber: Kalender Hari Libur Indonesia (Google Calendar). Tanggal yang sudah ada tidak akan diduplikasi.
                    </div>
                </form>
            </div>
        </div>

        {{-- Form Tambah Manual --}}
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-plus-circle me-2" style="color:var(--primary)"></i>Tambah Manual</span>
            </div>
            <div class="card-body">
                <form action="{{ route('libur.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Hari Libur</label>
                        <input type="text" name="nama_libur" class="form-control" placeholder="Contoh: Idul Fitri, Tahun Baru" value="{{ old('nama_libur') }}" required>
                    </div>
                    <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;padding:12px">
                        <i class="bi bi-calendar-plus"></i> Tambah Libur
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Daftar Hari Libur --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-calendar-event me-2" style="color:var(--primary)"></i>Daftar Hari Libur Nasional</span>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Hari Libur</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($liburs as $libur)
                    <tr>
                        <td style="font-weight:600">
                            {{ $libur->tanggal->locale('id')->isoFormat('ddd, D MMMM Y') }}
                        </td>
                        <td>{{ $libur->nama_libur }}</td>
                        <td>
                            <form action="{{ route('libur.destroy', $libur) }}" method="POST" onsubmit="return confirm('Hapus hari libur ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-outline btn-sm" style="color:#dc2626">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center;padding:48px;color:var(--gray-400)">
                            <i class="bi bi-calendar-x" style="font-size:32px;display:block;margin-bottom:12px;opacity:.3"></i>
                            Belum ada hari libur yang dicatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($liburs->hasPages())
        <div style="padding:16px;border-top:1px solid var(--gray-100)">
            {{ $liburs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
