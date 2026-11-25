<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'device_id')) {
                $table->string('device_id', 191)->nullable()->unique()->after('role');
            }
            if (!Schema::hasColumn('users', 'device_name')) {
                $table->string('device_name', 191)->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('users', 'device_bound_at')) {
                $table->timestamp('device_bound_at')->nullable()->after('device_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'device_id')) {
                $table->dropUnique(['device_id']);
                $table->dropColumn('device_id');
            }
            if (Schema::hasColumn('users', 'device_name')) {
                $table->dropColumn('device_name');
            }
            if (Schema::hasColumn('users', 'device_bound_at')) {
                $table->dropColumn('device_bound_at');
            }
        });
    }
};
