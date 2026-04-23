<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'pangkat_gol')) {
                $table->string('pangkat_gol', 100)->nullable()->after('jabatan');
            }
            if (!Schema::hasColumn('users', 'jenis_absensi')) {
                $table->enum('jenis_absensi', ['normal', 'shift'])->default('normal')->after('unit');
            }
            if (!Schema::hasColumn('users', 'role') || !$this->roleHasKepalaUnit()) {
                // Modify role enum to include kepala_unit if not already
                try {
                    \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pegawai','kepala_unit') DEFAULT 'pegawai'");
                } catch (\Exception $e) {
                    // Already correct
                }
            }
            if (!Schema::hasColumn('users', 'fingerprint_id')) {
                $table->string('fingerprint_id')->nullable()->after('foto');
            }
            if (!Schema::hasColumn('users', 'fingerprint_data')) {
                $table->longText('fingerprint_data')->nullable()->after('fingerprint_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['nik', 'pangkat_gol', 'jenis_absensi', 'fingerprint_id', 'fingerprint_data'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function roleHasKepalaUnit(): bool
    {
        try {
            $result = \DB::select("SHOW COLUMNS FROM users LIKE 'role'");
            if (!empty($result)) {
                return str_contains($result[0]->Type ?? '', 'kepala_unit');
            }
        } catch (\Exception $e) {}
        return false;
    }
};
