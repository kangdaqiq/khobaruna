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
        Schema::create('capaian_pembelajarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('mapel_id');
            $table->string('fase', 10)->default('E'); // E (Kelas X), F (Kelas XI & XII)
            $table->string('tingkat', 10)->nullable(); // 10, 11, 12
            $table->string('elemen'); // Nama Elemen CP (e.g. Menyimak, Membaca, dll)
            $table->text('deskripsi'); // Deskripsi Capaian Pembelajaran
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('mapel_id')->references('id')->on('mapels')->onDelete('cascade');
        });

        Schema::create('tujuan_pembelajarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('mapel_id');
            $table->unsignedBigInteger('cp_id')->nullable();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->string('tingkat', 10)->default('10'); // 10, 11, 12
            $table->string('fase', 10)->default('E');
            $table->string('semester', 10)->default('Ganjil'); // Ganjil, Genap
            $table->string('kode_tp', 30); // TP 1, TP 2, dsb (berkorelasi dg Sumatif 1, 2, ...)
            $table->text('deskripsi'); // Uraian Tujuan Pembelajaran
            $table->string('ringkasan_materi')->nullable(); // Ringkasan untuk frasa rapor
            $table->integer('urutan')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('mapel_id')->references('id')->on('mapels')->onDelete('cascade');
            $table->foreign('cp_id')->references('id')->on('capaian_pembelajarans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tujuan_pembelajarans');
        Schema::dropIfExists('capaian_pembelajarans');
    }
};
