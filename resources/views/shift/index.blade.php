@extends('layouts.app')
@section('title', 'Jadwal Shift')
@section('breadcrumb')Kehadiran / <span>Jadwal Shift</span>@endsection

@section('content')
<div style="font-size:22px;font-weight:800;color:var(--gray-900);margin-bottom:4px">Jadwal Shift</div>
<div style="font-size:13px;color:var(--gray-400);margin-bottom:24px">Kelola jadwal shift pegawai</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px">

    {{-- Form Tambah --}}
    <div class="card" style="align-self:start">
        <div class="card-header">
            <span><i class="bi bi-plus-circle me-2" style="color:var(--primary)"></i>Penetapan Shift</span>
        </div>
        <div class="card-body">
        @if(!auth()->user()->isAdmin() && auth()->user()->jenis_absensi !== 'shift')
            <div style="text-align:center;padding:32px 16px">
                <i class="bi bi-clock-history" style="font-size:40px;color:#94a3b8;display:block;margin-bottom:12px"></i>
                <div style="font-weight:700;font-size:15px;color:#374151;margin-bottom:6px">Absensi Normal</div>
                <div style="font-size:13px;color:#64748b;line-height:1.6">
                    Anda terdaftar sebagai pegawai <strong>absensi normal</strong>.<br>
                    Jadwal shift hanya tersedia untuk pegawai shift.<br>
                    Hubungi admin untuk mengubah jenis absensi.
                </div>
            </div>
        @else
            <form action="{{ route('shift.store') }}" method="POST">
                @csrf
                @if(auth()->user()->isAdmin())
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <label class="form-label" style="margin-bottom:0">Pilih Pegawai</label>
                        <button type="button" onclick="selectAllUsers()" class="btn btn-outline" style="font-size:10px;padding:2px 6px;border-radius:4px">Pilih Semua</button>
                    </div>
                    <select name="user_ids[]" id="userSelect" class="form-control form-select" multiple style="height: 120px" required>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->unit ?? '-' }})</option>
                        @endforeach
                    </select>
                    <div class="form-text">Tahan Ctrl/Cmd untuk memilih lebih dari satu</div>
                </div>
                @endif

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" min="{{ date('Y-m-d') }}"
                            value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required onchange="syncEndDate()">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" id="tanggalSelesai" class="form-control"
                            value="{{ old('tanggal_selesai', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                    <div class="form-check" style="display:flex;align-items:center;padding:10px;border:1.5px solid var(--gray-200);border-radius:10px;background:var(--gray-50)">
                        <input class="form-check-input" type="checkbox" name="skip_sabtu" id="skipSabtu" style="margin-right:10px;width:18px;height:18px">
                        <label class="form-check-label" for="skipSabtu" style="font-size:12px;font-weight:600;margin-bottom:0">Lewati Sabtu</label>
                    </div>
                    <div class="form-check" style="display:flex;align-items:center;padding:10px;border:1.5px solid var(--gray-200);border-radius:10px;background:var(--gray-50)">
                        <input class="form-check-input" type="checkbox" name="skip_minggu" id="skipMinggu" style="margin-right:10px;width:18px;height:18px">
                        <label class="form-check-label" for="skipMinggu" style="font-size:12px;font-weight:600;margin-bottom:0">Lewati Minggu</label>
                    </div>
                </div>

                <div class="form-check" style="display:flex;align-items:center;padding:10px;border:1.5px solid var(--gray-200);border-radius:10px;background:var(--gray-50);margin-bottom:18px">
                    <input class="form-check-input" type="checkbox" name="skip_libur" id="skipLibur" style="margin-right:10px;width:18px;height:18px">
                    <label class="form-check-label" for="skipLibur" style="font-size:12px;font-weight:600;margin-bottom:0">Lewati Hari Libur Nasional (Tanggal Merah)</label>
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Shift</label>
                    <select name="jenis_shift" class="form-control form-select" id="jenisShift" onchange="setJam()" required>
                        <option value="pagi">🌅 Pagi (07:00 - 16:00 / Jumat 17:00)</option>
                        <option value="siang">☀️ Siang (14:00 - 21:00)</option>
                        <option value="malam">🌙 Malam (21:00 - 07:00)</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div class="form-group">
                        <label class="form-label">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="jamMasuk" class="form-control" value="07:00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jam Keluar</label>
                        <input type="time" name="jam_keluar" id="jamKeluar" class="form-control" value="16:00" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan <span style="color:var(--gray-400);font-weight:400">(opsional)</span></label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Catatan tambahan..."
                        value="{{ old('keterangan') }}">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">
                    <i class="bi bi-save"></i> Simpan Shift
                </button>
            </form>
        @endif
        </div>
    </div>

    {{-- Daftar Shift --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-calendar3 me-2" style="color:var(--primary)"></i>Daftar Shift</span>
            <form style="display:flex;gap:8px">
                <select name="bulan" class="form-control form-select" style="width:130px;padding:6px 10px;font-size:13px" onchange="this.form.submit()">
                    @for($i=1;$i<=12;$i++)
                    <option value="{{ $i }}" {{ $bulan==$i?'selected':'' }}>
                        {{ \Carbon\Carbon::create(null,$i)->locale('id')->isoFormat('MMMM') }}
                    </option>
                    @endfor
                </select>
                <select name="tahun" class="form-control form-select" style="width:90px;padding:6px 10px;font-size:13px" onchange="this.form.submit()">
                    @for($y=date('Y');$y<=date('Y')+1;$y++)
                    <option value="{{ $y }}" {{ $tahun==$y?'selected':'' }}>{{ $y }}</option>
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
                        <th>Shift</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Keterangan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                    @php
                        $shiftColors = ['pagi'=>['#fff7ed','#c2410c'], 'siang'=>['#eff6ff','#1d4ed8'], 'malam'=>['#1e1b4b','#a5b4fc']];
                        $sc = $shiftColors[$shift->jenis_shift] ?? ['#f3f4f6','#374151'];
                    @endphp
                    <tr>
                        <td style="font-weight:600">
                            {{ \Carbon\Carbon::parse($shift->tanggal)->locale('id')->isoFormat('ddd, D MMM Y') }}
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td>{{ $shift->user->name ?? '-' }}</td>
                        @endif
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $sc[0] }};color:{{ $sc[1] }}">
                                {{ ucfirst($shift->jenis_shift) }}
                            </span>
                        </td>
                        <td style="font-weight:600">{{ $shift->jam_masuk }}</td>
                        <td style="font-weight:600">{{ $shift->jam_keluar }}</td>
                        <td style="color:var(--gray-400);font-size:12px">{{ $shift->keterangan ?? '-' }}</td>
                        <td>
                            <form action="{{ route('shift.destroy', $shift) }}" method="POST"
                                onsubmit="return confirm('Hapus shift ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-icon btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:48px;color:var(--gray-400)">
                            <i class="bi bi-calendar-x" style="font-size:36px;display:block;margin-bottom:10px;opacity:.3"></i>
                            Tidak ada shift pada periode ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
const shiftMap = { pagi:['07:00','16:00'], siang:['14:00','21:00'], malam:['21:00','07:00'] };

function setJam() {
    const v = document.getElementById('jenisShift').value;
    const dateInput = document.getElementsByName('tanggal_mulai')[0].value;
    
    document.getElementById('jamMasuk').value = shiftMap[v][0];
    
    let jamKeluar = shiftMap[v][1];
    
    // Logika Khusus Jumat (Pagi)
    if (v === 'pagi' && dateInput) {
        const day = new Date(dateInput).getDay();
        if (day === 5) { // 5 = Jumat
            jamKeluar = '17:00';
        }
    }
    
    document.getElementById('jamKeluar').value = jamKeluar;
}

function selectAllUsers() {
    const select = document.getElementById('userSelect');
    if (!select) return;
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = true;
    }
}

function syncEndDate() {
    const start = document.getElementsByName('tanggal_mulai')[0].value;
    const endInput = document.getElementById('tanggalSelesai');
    if (start && (!endInput.value || endInput.value < start)) {
        endInput.value = start;
        endInput.min = start;
    }
}

// Init min date for end date
document.addEventListener('DOMContentLoaded', () => {
    syncEndDate();
});
</script>
@endpush
@endsection
