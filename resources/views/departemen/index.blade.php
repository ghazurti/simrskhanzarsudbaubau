@extends('layouts.app')
@section('title', 'Data Departemen')
@section('breadcrumb')Manajemen / <span>Departemen</span>@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
        <div style="font-size:22px;font-weight:800;color:var(--gray-900)">Data Departemen</div>
        <div style="font-size:13px;color:var(--gray-400);margin-top:3px">Kelola data departemen / unit kerja RSUD Kota Baubau</div>
    </div>
    <div style="display:flex;gap:10px">
        <button type="button" onclick="document.getElementById('importModal').style.display='flex'"
            class="btn" style="padding:10px 18px;border-radius:10px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;font-weight:600;display:flex;align-items:center;gap:8px">
            <i class="bi bi-file-earmark-arrow-up"></i> Impor Excel / CSV
        </button>
        <a href="{{ route('departemen.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Departemen
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span style="color:var(--gray-500);font-weight:400;font-size:13px">
            Total: <strong style="color:var(--gray-900)">{{ $departments->total() }}</strong> departemen
        </span>
    </div>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th style="width:60px">ID</th>
                    <th style="width:100px">Kode</th>
                    <th>Nama Departemen</th>
                    <th>Keterangan</th>
                    <th>Tgl. Dibuat</th>
                    <th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $d)
                <tr>
                    <td style="color:var(--gray-400);font-family:monospace">#{{ $d->id }}</td>
                    <td style="font-weight:700;color:var(--gray-700)">{{ $d->kode ?? '-' }}</td>
                    <td>
                        <div style="font-weight:600;font-size:14px;color:var(--primary)">{{ $d->nama }}</div>
                    </td>
                    <td style="color:var(--gray-500);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $d->keterangan ?? '-' }}
                    </td>
                    <td style="color:var(--gray-400);font-size:12px">
                        {{ $d->created_at->format('d M Y') }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end">
                            <a href="{{ route('departemen.edit', $d) }}" class="btn btn-outline btn-sm btn-icon" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('departemen.destroy', $d) }}" method="POST"
                                onsubmit="return confirm('Hapus departemen {{ addslashes($d->nama) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-icon btn-sm" title="Hapus"
                                    style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:6px;cursor:pointer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:48px;color:var(--gray-400)">
                        <i class="bi bi-building" style="font-size:36px;display:block;margin-bottom:10px;opacity:.3"></i>
                        Tidak ada data departemen
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:20px;border-top:1px solid var(--gray-100)">
        <div style="display:flex;flex-direction:column;gap:12px">
            {{ $departments->links() }}
            <div class="pagination-info">
                Menampilkan <strong>{{ $departments->firstItem() ?? 0 }}</strong> - <strong>{{ $departments->lastItem() ?? 0 }}</strong> dari <strong>{{ $departments->total() }}</strong> departemen
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Modal Import --}}
<div id="importModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:100%;max-width:480px;border-radius:16px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:18px;font-weight:800;color:#1e293b">Impor Data Departemen</h3>
            <button onclick="document.getElementById('importModal').style.display='none'" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:20px"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('departemen.import') }}" method="POST" enctype="multipart/form-data" style="padding:24px">
            @csrf
            <div style="margin-bottom:16px">
                <label style="display:block;font-weight:600;font-size:14px;color:#475569;margin-bottom:8px">Pilih File <span style="color:#dc2626">*</span></label>
                <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                    class="form-control" style="padding:10px;border-radius:8px;border:1px solid #e2e8f0;width:100%">
                <p style="font-size:12px;color:#64748b;margin-top:8px">Format: <strong>.xlsx, .xls, .csv</strong></p>
            </div>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:16px;font-size:12px;color:#475569;line-height:1.8">
                <div style="font-weight:700;margin-bottom:4px;color:#1e293b"><i class="bi bi-table me-1"></i>Kolom yang diperlukan:</div>
                <div><code>kode</code> — wajib, unik (contoh: IGD, POL-IN)</div>
                <div><code>nama</code> — wajib, nama lengkap departemen</div>
                <div><code>keterangan</code> — opsional</div>
            </div>

            <a href="{{ route('departemen.template') }}" style="display:inline-flex;align-items:center;gap:8px;color:#16a34a;font-weight:700;font-size:13px;text-decoration:none;margin-bottom:20px">
                <i class="bi bi-file-earmark-excel-fill" style="font-size:16px"></i> Unduh Template Excel
            </a>

            <div style="display:flex;gap:12px;justify-content:flex-end;padding-top:16px;border-top:1px solid #f1f5f9">
                <button type="button" onclick="document.getElementById('importModal').style.display='none'"
                    style="padding:10px 20px;border-radius:10px;background:#f1f5f9;border:none;color:#475569;font-weight:600;cursor:pointer">Batal</button>
                <button type="submit" style="padding:10px 24px;border-radius:10px;background:var(--primary);border:none;color:#fff;font-weight:700;cursor:pointer">
                    <i class="bi bi-upload me-1"></i> Mulai Impor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('importModal');
        if (e.target === modal) modal.style.display = 'none';
    });
</script>
