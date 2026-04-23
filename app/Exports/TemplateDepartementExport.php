<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TemplateDepartementExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    public function array(): array
    {
        return [
            ['kode', 'nama', 'keterangan'],
            ['IGD', 'Instalasi Gawat Darurat', 'Unit pelayanan gawat darurat 24 jam'],
            ['POL-IN', 'Poli Penyakit Dalam', ''],
            ['POL-ANK', 'Poli Anak', ''],
            ['POL-KEB', 'Poli Kebidanan', ''],
            ['LAB', 'Laboratorium', 'Unit pemeriksaan laboratorium'],
            ['RAD', 'Radiologi', ''],
            ['FAR', 'Farmasi / Apotek', ''],
            ['ICU', 'ICU / ICCU', 'Intensive Care Unit'],
            ['KEU', 'Keuangan', 'Bagian administrasi keuangan'],
            ['TU', 'Tata Usaha', ''],
            ['--- KETERANGAN ---', '', ''],
            ['kode: singkatan unik departemen (maks. 10 karakter)', '', ''],
            ['nama: nama lengkap departemen (wajib, unik)', '', ''],
            ['keterangan: deskripsi singkat (opsional)', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A11:C11');
        $sheet->mergeCells('A12:C12');
        $sheet->mergeCells('A13:C13');
        $sheet->mergeCells('A14:C14');

        return [
            1 => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            11 => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
            ],
            12 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
            13 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
            14 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']], 'font' => ['color' => ['rgb' => '78350F'], 'italic' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 36, 'C' => 40];
    }

    public function title(): string
    {
        return 'Template Departemen';
    }
}
