<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TemplatePegawaiExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    public function array(): array
    {
        return [
            // Row 1: Header
            ['nama', 'email', 'nik', 'nip', 'no_hp', 'jabatan', 'pangkat_gol', 'unit', 'jenis_absensi', 'role'],
            // Row 2: Contoh data
            ['Budi Santoso', '', '1234567890123456', '198001012005011001', '08123456789', 'Perawat Ahli Muda', 'Penata / III-c', 'IGD', 'normal', 'pegawai'],
            ['Siti Rahayu', '', '1234567890123457', '', '08987654321', 'Dokter Spesialis', 'Pembina / IV-a', 'Poli Dalam', 'shift', 'pegawai'],
            // Row 3: Keterangan
            ['--- KETERANGAN ---', '', '', '', '', '', '', '', '', ''],
            ['email: kosongkan jika tidak ada (otomatis: nik@rsud-baubau.go.id)', '', '', '', '', '', '', '', '', ''],
            ['nip: kosongkan jika bukan PNS', '', '', '', '', '', '', '', '', ''],
            ['jenis_absensi: isi "normal" atau "shift"', '', '', '', '', '', '', '', '', ''],
            ['role: isi "pegawai" atau "kepala_unit"', '', '', '', '', '', '', '', '', ''],
            ['password default: NIK pegawai', '', '', '', '', '', '', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Merge keterangan cells
        foreach (range(4, 9) as $row) {
            $sheet->mergeCells("A{$row}:J{$row}");
        }

        return [
            1 => [
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1D4ED8'],
                ],
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]],
            3 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]],
            4 => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
            ],
            5 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
            6 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
            7 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
            8 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
            9 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 28,
            'C' => 20,
            'D' => 22,
            'E' => 16,
            'F' => 24,
            'G' => 18,
            'H' => 18,
            'I' => 14,
            'J' => 14,
        ];
    }

    public function title(): string
    {
        return 'Template Pegawai';
    }
}
