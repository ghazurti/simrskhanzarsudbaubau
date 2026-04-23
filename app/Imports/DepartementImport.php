<?php

namespace App\Imports;

use App\Models\Department;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DepartementImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $errors   = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $nama = trim($row['nama'] ?? '');
                $kode = trim($row['kode'] ?? '');

                if (empty($nama) || empty($kode)) continue;

                Department::updateOrCreate(
                    ['kode' => $kode],
                    [
                        'nama'        => $nama,
                        'keterangan'  => trim($row['keterangan'] ?? '') ?: null,
                    ]
                );

                $this->imported++;
            } catch (\Exception $e) {
                $this->errors++;
            }
        }
    }
}
