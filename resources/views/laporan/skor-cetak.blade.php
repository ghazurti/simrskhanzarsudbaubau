<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Skor Kehadiran — {{ $pegawai->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
            padding: 10px;
        }

        /* Print setup */
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }

        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        /* Identity Table Style from Image */
        .identity-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .identity-header-table th, .identity-header-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            text-align: left;
            vertical-align: middle;
        }
        .identity-header-table th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        /* Scoring table */
        .score-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 20px;
        }
        .score-table th, .score-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }
        .score-table thead th {
            background: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .score-table .center { text-align: center; }
        .score-table .kode { text-align: center; font-weight: bold; }
        
        .total-row td {
            font-weight: bold;
            text-align: right;
            padding-right: 15px;
        }

        /* Signature */
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-box { text-align: center; width: 250px; }
        
        /* Print utilities */
        .print-bar {
            background: #f4f4f4;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            padding: 5px 15px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="print-bar no-print">
    <a href="{{ route('skor.index', ['user_id'=>$pegawai->id,'bulan'=>$bulan,'tahun'=>$tahun]) }}" style="text-decoration:none; color:#333;">
        ← Kembali
    </a>
    <strong>Preview Cetak Skor Kehadiran</strong>
    <button class="btn-print" onclick="window.print()">Cetak PDF</button>
</div>

<div style="text-align: left;">
    <div class="doc-title">A. LEMBAR PERHITUNGAN SKOR KEHADIRAN PEGAWAI</div>
    <div style="font-size: 10pt; margin-bottom: 10px;">
        Periode Penilaian : Bulan {{ \Carbon\Carbon::create($tahun,$bulan)->locale('id')->isoFormat('MMMM YYYY') }}<br>
        SKPD : DINAS KESEHATAN / RSUD KOTA BAUBAU
    </div>
</div>

<table class="identity-header-table">
    <thead>
        <tr>
            <th style="width: 40px;">No.</th>
            <th style="width: 150px;">Uraian</th>
            <th>Pejabat Penilai</th>
            <th>Pegawai Yang Dinilai</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;">1</td>
            <td>Nama</td>
            <td>{{ $pejabatPenilai->name ?? '-' }}</td>
            <td>{{ $pegawai->name }}</td>
        </tr>
        <tr>
            <td style="text-align: center;">2</td>
            <td>NIP</td>
            <td>{{ $pejabatPenilai->nip ?? '-' }}</td>
            <td>{{ $pegawai->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td style="text-align: center;">3</td>
            <td>Status</td>
            <td>{{ $pejabatPenilai->pangkat_gol ?? '-' }}</td>
            <td>{{ $pegawai->pangkat_gol ?? '-' }}</td>
        </tr>
        <tr>
            <td style="text-align: center;">4</td>
            <td>Jabatan</td>
            <td>{{ $pejabatPenilai->jabatan ?? '-' }}</td>
            <td>{{ $pegawai->jabatan ?? '-' }}</td>
        </tr>
    </tbody>
</table>

<table class="score-table">
    <thead>
        <tr>
            <th rowspan="2" style="width: 30px;">No</th>
            <th rowspan="2" style="width: 100px;">Indikator</th>
            <th rowspan="2" style="width: 40px;">Kode</th>
            <th rowspan="2">Kriteria</th>
            <th rowspan="2" style="width: 50px;">%</th>
            <th colspan="3">Hasil Pengukuran</th>
        </tr>
        <tr>
            <th style="width: 50px;">Kali<br>TL</th>
            <th style="width: 50px;">Kali<br>PSW</th>
            <th style="width: 60px;">Jumlah</th>
        </tr>
        <tr>
            <th colspan="5" style="text-align: left; padding-left: 10px;">Ketidakhadiran</th>
            <th>TL</th>
            <th>PSW</th>
            <th>100</th>
        </tr>
    </thead>
    <tbody>
        {{-- Row KT1 --}}
        <tr>
            <td class="center">1</td>
            <td rowspan="6" class="center" style="font-weight: bold;">Ketidakhadiran</td>
            <td class="kode">KT1</td>
            <td>{{ $skor['detail']['KT1']['label'] }}</td>
            <td class="center">{{ number_format($skor['detail']['KT1']['persen'], 2) }}</td>
            <td class="center">{{ collect($skor['hari'])->where('tl', '>', 0)->where('tl', '<=', 30)->count() }}</td>
            <td class="center">{{ collect($skor['hari'])->where('psw', '>', 0)->where('psw', '<=', 30)->count() }}</td>
            <td class="center">{{ $skor['detail']['KT1']['jumlah'] > 0 ? number_format($skor['detail']['KT1']['jumlah'], 2) : '0' }}</td>
        </tr>
        {{-- Row KT2 --}}
        <tr>
            <td class="center"></td>
            <td class="kode">KT2</td>
            <td>{{ $skor['detail']['KT2']['label'] }}</td>
            <td class="center">{{ number_format($skor['detail']['KT2']['persen'], 2) }}</td>
            <td class="center">{{ collect($skor['hari'])->where('tl', '>', 30)->where('tl', '<=', 60)->count() }}</td>
            <td class="center">{{ collect($skor['hari'])->where('psw', '>', 30)->where('psw', '<=', 60)->count() }}</td>
            <td class="center">{{ $skor['detail']['KT2']['jumlah'] > 0 ? number_format($skor['detail']['KT2']['jumlah'], 2) : '0' }}</td>
        </tr>
        {{-- Row KT3 --}}
        <tr>
            <td class="center"></td>
            <td class="kode">KT3</td>
            <td>{{ $skor['detail']['KT3']['label'] }}</td>
            <td class="center">{{ number_format($skor['detail']['KT3']['persen'], 2) }}</td>
            <td class="center">{{ collect($skor['hari'])->where('tl', '>', 60)->where('tl', '<=', 90)->count() }}</td>
            <td class="center">{{ collect($skor['hari'])->where('psw', '>', 60)->where('psw', '<=', 90)->count() }}</td>
            <td class="center">{{ $skor['detail']['KT3']['jumlah'] > 0 ? number_format($skor['detail']['KT3']['jumlah'], 2) : '0' }}</td>
        </tr>
        {{-- Row KT4 --}}
        <tr>
            <td class="center"></td>
            <td class="kode">KT4</td>
            <td>{{ $skor['detail']['KT4']['label'] }}</td>
            <td class="center">{{ number_format($skor['detail']['KT4']['persen'], 1) }}</td>
            <td class="center">{{ collect($skor['hari'])->where('tl', '>', 90)->count() }}</td>
            <td class="center">{{ collect($skor['hari'])->where('psw', '>', 90)->count() }}</td>
            <td class="center">{{ $skor['detail']['KT4']['jumlah'] > 0 ? number_format($skor['detail']['KT4']['jumlah'], 2) : '0' }}</td>
        </tr>
        {{-- Row KT5 --}}
        <tr>
            <td class="center"></td>
            <td class="kode">KT5</td>
            <td>{{ $skor['detail']['KT5']['label'] }}</td>
            <td class="center">{{ number_format($skor['detail']['KT5']['persen'], 2) }}</td>
            <td class="center">{{ $skor['detail']['KT5']['kali'] }}</td>
            <td class="center">0</td>
            <td class="center">{{ $skor['detail']['KT5']['jumlah'] > 0 ? number_format($skor['detail']['KT5']['jumlah'], 2) : '0' }}</td>
        </tr>
        {{-- Row KT6 --}}
        <tr>
            <td class="center"></td>
            <td class="kode">KT6</td>
            <td>{{ $skor['detail']['KT6']['label'] }}</td>
            <td class="center">{{ number_format($skor['detail']['KT6']['persen'], 1) }}</td>
            <td class="center">{{ $skor['detail']['KT6']['kali'] }}</td>
            <td class="center">0</td>
            <td class="center">{{ $skor['detail']['KT6']['jumlah'] > 0 ? number_format($skor['detail']['KT6']['jumlah'], 2) : '0' }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="7">Total</td>
            <td style="text-align: center;">{{ number_format($skor['skor_akhir'], 2) }}</td>
        </tr>
    </tfoot>
</table>

<div class="signature-section">
    <div class="signature-box">
        <div style="margin-bottom: 50px;">Pejabat Penilai</div>
        <div style="font-weight: bold; text-decoration: underline;">{{ $pejabatPenilai->name ?? '.........................................' }}</div>
        <div>NIP. {{ $pejabatPenilai->nip ?? '.........................................' }}</div>
    </div>
</div>

</body>
</html>

