<?php

namespace App\Exports;

use App\Models\Lembur;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RekapAbsensiExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected int $bulan;
    protected int $tahun;
    protected ?string $unit;

    public function __construct(int $bulan, int $tahun, ?string $unit = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->unit  = $unit;
    }

    public function array(): array
    {
        $users = User::where('role', 'pegawai')
            ->when($this->unit, fn($q) => $q->where('unit', $this->unit))
            ->with(['absensis' => function ($q) {
                $q->whereMonth('tanggal', $this->bulan)->whereYear('tanggal', $this->tahun);
            }])
            ->orderBy('unit')->orderBy('name')
            ->get();

        $rows = [];
        foreach ($users as $i => $u) {
            $stats  = $u->absensis->groupBy('status');
            $hadir  = ($stats['hadir']     ?? collect())->count();
            $telat  = ($stats['terlambat'] ?? collect())->count();
            $izin   = ($stats['izin']      ?? collect())->count();
            $sakit  = ($stats['sakit']     ?? collect())->count();
            $alpha  = ($stats['alpha']     ?? collect())->count();
            $lembur = Lembur::where('user_id', $u->id)
                ->whereMonth('tanggal', $this->bulan)
                ->whereYear('tanggal', $this->tahun)
                ->where('status', 'approved')
                ->count();

            $rows[] = [
                $i + 1,
                $u->name,
                $u->nip ?? '-',
                $u->unit ?? '-',
                $hadir,
                $telat,
                $izin,
                $sakit,
                $alpha,
                $lembur,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        $namaBulan = Carbon::create($this->tahun, $this->bulan)->locale('id')->isoFormat('MMMM YYYY');
        return [
            ['REKAPITULASI ABSENSI PEGAWAI'],
            [($this->unit ? 'Unit: ' . $this->unit . ' | ' : '') . 'Periode: ' . $namaBulan],
            [],
            ['No', 'Nama Pegawai', 'NIP', 'Unit/Bagian', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha', 'Lembur'],
        ];
    }

    public function title(): string
    {
        return 'Rekap ' . Carbon::create($this->tahun, $this->bulan)->format('M Y');
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '16A34A'],
                ],
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 28,
            'C' => 20,
            'D' => 18,
            'E' => 8,
            'F' => 10,
            'G' => 8,
            'H' => 8,
            'I' => 8,
            'J' => 8,
        ];
    }
}
