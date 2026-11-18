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
        Schema::create('absensi_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
            $table->foreignId('kelas_id')->nullable()->constrained('kelases')->onDelete('set null');
            $table->string('tahun_ajaran', 20)->default('2024/2025');
            $table->string('semester', 20)->default('Ganjil');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Rekap Kehadiran
            $table->unsignedInteger('masuk')->default(0)->comment('Total Masuk (Hadir + Terlambat)');
            $table->unsignedInteger('hadir')->default(0);
            $table->unsignedInteger('terlambat')->default(0);
            $table->unsignedInteger('izin')->default(0);
            $table->unsignedInteger('sakit')->default(0);
            $table->unsignedInteger('alpha')->default(0);
            $table->unsignedInteger('bolos')->default(0);
            $table->unsignedInteger('total_hari')->default(0);
            $table->decimal('persentase', 5, 2)->default(0.00);
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'siswa_id', 'tahun_ajaran', 'semester'], 'unique_rekap_siswa_semester');
            $table->index(['school_id', 'kelas_id', 'tahun_ajaran', 'semester'], 'idx_rekap_kelas_semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_siswas');
    }
};
