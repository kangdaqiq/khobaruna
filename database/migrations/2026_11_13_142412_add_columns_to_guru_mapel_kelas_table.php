<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guru_mapel_kelas', function (Blueprint $table) {
            if (!Schema::hasColumn('guru_mapel_kelas', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('guru_mapel_kelas', 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 20)->default('2024/2025')->after('kelas_id');
            }
            if (!Schema::hasColumn('guru_mapel_kelas', 'semester')) {
                $table->string('semester', 10)->default('Ganjil')->after('tahun_ajaran');
            }
            if (!Schema::hasColumn('guru_mapel_kelas', 'sync_id')) {
                $table->unsignedBigInteger('sync_id')->nullable()->after('semester');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guru_mapel_kelas', function (Blueprint $table) {
            $table->dropColumn(['school_id', 'tahun_ajaran', 'semester', 'sync_id']);
        });
    }
};
