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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('titimangsa_tempat')->nullable()->default('Tumijajar')->after('headmaster_nip');
            $table->date('titimangsa_tanggal')->nullable()->after('titimangsa_tempat');
            $table->string('headmaster_signature')->nullable()->after('titimangsa_tanggal');
            $table->string('school_stamp')->nullable()->after('headmaster_signature');
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('foto');
        });

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->text('catatan_wali_kelas')->nullable()->after('catatan');
            $table->string('kenaikan_kelas')->nullable()->after('catatan_wali_kelas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['titimangsa_tempat', 'titimangsa_tanggal', 'headmaster_signature', 'school_stamp']);
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn(['signature']);
        });

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->dropColumn(['catatan_wali_kelas', 'kenaikan_kelas']);
        });
    }
};
