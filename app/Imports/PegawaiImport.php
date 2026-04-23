<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PegawaiImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $errors   = 0;
    public array $errorMessages = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $nik = trim($row['nik'] ?? '');
                if (empty($nik)) continue;

                $jenisAbsensi = strtolower(trim($row['jenis_absensi'] ?? 'normal'));
                if (!in_array($jenisAbsensi, ['normal', 'shift'])) {
                    $jenisAbsensi = 'normal';
                }

                $role = strtolower(trim($row['role'] ?? 'pegawai'));
                if (!in_array($role, ['pegawai', 'kepala_unit'])) {
                    $role = 'pegawai';
                }

                $email = trim($row['email'] ?? '') ?: ($nik . '@rsud-baubau.go.id');

                User::updateOrCreate(
                    ['nik' => $nik],
                    [
                        'name'          => trim($row['nama'] ?? ''),
                        'email'         => $email,
                        'nip'           => trim($row['nip'] ?? '') ?: null,
                        'no_hp'         => trim($row['no_hp'] ?? '') ?: null,
                        'jabatan'       => trim($row['jabatan'] ?? '') ?: null,
                        'pangkat_gol'   => trim($row['pangkat_gol'] ?? '') ?: null,
                        'unit'          => trim($row['unit'] ?? '') ?: null,
                        'jenis_absensi' => $jenisAbsensi,
                        'role'          => $role,
                        'password'      => Hash::make($nik),
                    ]
                );

                $this->imported++;
            } catch (\Exception $e) {
                $this->errors++;
                $this->errorMessages[] = 'Baris ' . ($index + 2) . ': ' . $e->getMessage();
            }
        }
    }
}
