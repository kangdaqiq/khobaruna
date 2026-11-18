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
        // 1. Schools (Profil Sekolah)
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('npsn')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('headmaster_name')->nullable();
            $table->string('headmaster_nip')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        // 2. Kelases (Kelas / Rombel)
        Schema::create('kelases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('nama_kelas');
            $table->string('tingkat')->nullable();
            $table->string('jurusan')->nullable();
            $table->unsignedBigInteger('wali_kelas_id')->nullable()->index();
            $table->string('wali_kelas_nama')->nullable();
            $table->integer('jumlah_siswa')->default(0);
            $table->timestamps();
        });

        // 3. Gurus (Data Guru & Pendidik)
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->string('tgl_lahir')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('email')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_wali_kelas')->default(false);
            $table->foreignId('kelas_id')->nullable()->constrained('kelases')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Siswas (Data Siswa & Peserta Didik)
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelases')->nullOnDelete();
            $table->string('nama');
            $table->string('nis')->nullable()->index();
            $table->string('nisn')->nullable()->index();
            $table->string('kelas_nama')->nullable();
            $table->string('tgl_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('wa_ortu')->nullable();
            $table->string('foto')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->timestamps();
        });

        // 5. Mapels (Mata Pelajaran)
        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('nama_mapel');
            $table->string('kode_mapel')->nullable();
            $table->string('kelompok')->nullable(); // Umum, Kejuruan, Muatan Lokal
            $table->timestamps();
        });

        // 6. Guru Assignments (Pemetaan Guru Mengajar Mapel di Kelas)
        Schema::create('guru_mapel_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelases')->cascadeOnDelete();
            $table->timestamps();
        });

        // 7. Sync Logs (Catatan Riwayat Sinkronisasi dari Web Eksternal)
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_url')->nullable();
            $table->string('status')->default('success'); // success, failed, in_progress
            $table->integer('total_gurus')->default(0);
            $table->integer('total_siswas')->default(0);
            $table->integer('total_kelases')->default(0);
            $table->integer('total_mapels')->default(0);
            $table->integer('total_users_created')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('guru_mapel_kelas');
        Schema::dropIfExists('mapels');
        Schema::dropIfExists('siswas');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('kelases');
        Schema::dropIfExists('schools');
    }
};

