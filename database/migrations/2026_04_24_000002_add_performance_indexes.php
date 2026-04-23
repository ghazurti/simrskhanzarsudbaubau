<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            if (!$this->indexExists('absensis', 'absensis_tanggal_status_index')) {
                $table->index(['tanggal', 'status'], 'absensis_tanggal_status_index');
            }
            if (!$this->indexExists('absensis', 'absensis_user_id_tanggal_index')) {
                $table->index(['user_id', 'tanggal'], 'absensis_user_id_tanggal_index');
            }
            if (!$this->indexExists('absensis', 'absensis_tanggal_check_out_index')) {
                $table->index(['tanggal', 'check_out'], 'absensis_tanggal_check_out_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_unit_role_index')) {
                $table->index(['unit', 'role'], 'users_unit_role_index');
            }
        });

        Schema::table('izins', function (Blueprint $table) {
            if (!$this->indexExists('izins', 'izins_tanggal_mulai_selesai_index')) {
                $table->index(['tanggal_mulai', 'tanggal_selesai'], 'izins_tanggal_mulai_selesai_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropIndexIfExists('absensis_tanggal_status_index');
            $table->dropIndexIfExists('absensis_user_id_tanggal_index');
            $table->dropIndexIfExists('absensis_tanggal_check_out_index');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('users_unit_role_index');
        });
        Schema::table('izins', function (Blueprint $table) {
            $table->dropIndexIfExists('izins_tanggal_mulai_selesai_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};
