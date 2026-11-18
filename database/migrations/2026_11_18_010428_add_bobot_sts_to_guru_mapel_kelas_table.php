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
            if (!Schema::hasColumn('guru_mapel_kelas', 'bobot_sts')) {
                $table->unsignedTinyInteger('bobot_sts')->default(20)->after('bobot_harian');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guru_mapel_kelas', function (Blueprint $table) {
            if (Schema::hasColumn('guru_mapel_kelas', 'bobot_sts')) {
                $table->dropColumn('bobot_sts');
            }
        });
    }
};
