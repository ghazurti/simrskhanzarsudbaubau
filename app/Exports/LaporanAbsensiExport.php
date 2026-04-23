<?php

namespace App\Exports;

use App\Models\Absensi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanAbsensiExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected int $bulan;
    protected int $tahun;
    protected ?int $userId;
    protected ?string $unit;

    public function __construct(int $bulan, int $tahun, ?int $userId = null, ?string $unit = null)
    {
        $this->bulan  = $bulan;
        $this->tahun  = $tahun;
        $this->userId = $userId;
        $this->unit   = $unit;
    }

    public function array(): array
    {
        $absensis = Absensi::with('user')
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->unit, fn($q) => $q->whereHas('user', fn($q2) => $q2->where('unit', $this->unit)))
            ->orderBy('tanggal')
            ->get();

        $rows = [];
        foreach ($absensis as $i => $a) {
            $durasi = null;
            if ($a->check_in && $a->check_out) {
                $m = Carbon::parse($a->check_in)->diffInMinutes(Carbon::parse($a->check_out));
                $durasi = floor($m / 60) . 'j ' . ($m % 60) . 'm';
            }

            $rows[] = [
                $i + 1,
                $a->user->name ?? '-',
                $a->user->nip ?? '-',
                $a->user->unit ?? '-',
                Carbon::parse($a->tanggal)->format('d/m/Y'),
                $a->check_in ? Carbon::parse($a->check_in)->format('H:i') : '-',
                $a->check_out ? Carbon::parse($a->check_out)->format('H:i') : '-',
                $durasi ?? '-',
                strtoupper($a->status),
                $a->keterangan ?? '-',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        $namaBulan = Carbon::create($this->tahun, $this->bulan)->locale('id')->isoFormat('MMMM YYYY');
        return [
            ['LAPORAN ABSENSI PEGAWAI'],
            [($this->unit ? 'Unit: ' . $this->unit . ' | ' : '') . 'Periode: ' . $namaBulan],
            [],
            ['No', 'Nama', 'NIP', 'Unit', 'Tanggal', 'Check In', 'Check Out', 'Durasi', 'Status', 'Keterangan'],
        ];
    }

    public function title(): string
    {
        return 'Laporan ' . Carbon::create($this->tahun, $this->bulan)->format('M Y');
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
                    'startColor' => ['rgb' => '2563EB'],
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
            'E' => 12,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 12,
            'J' => 25,
        ];
    }
}
