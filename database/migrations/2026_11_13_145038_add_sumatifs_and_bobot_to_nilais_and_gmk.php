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
        Schema::table('nilais', function (Blueprint $table) {
            $table->decimal('sumatif_1', 5, 2)->nullable()->after('guru_id');
            $table->decimal('sumatif_2', 5, 2)->nullable()->after('sumatif_1');
            $table->decimal('sumatif_3', 5, 2)->nullable()->after('sumatif_2');
            $table->decimal('sumatif_4', 5, 2)->nullable()->after('sumatif_3');
            $table->decimal('sumatif_5', 5, 2)->nullable()->after('sumatif_4');
            $table->decimal('sumatif_6', 5, 2)->nullable()->after('sumatif_5');
            $table->decimal('rata_sumatif_harian', 5, 2)->nullable()->after('sumatif_6');
        });

        Schema::table('guru_mapel_kelas', function (Blueprint $table) {
            $table->unsignedTinyInteger('jumlah_sumatif')->default(3)->after('semester');
            $table->unsignedTinyInteger('bobot_harian')->default(60)->after('jumlah_sumatif');
            $table->unsignedTinyInteger('bobot_sas')->default(40)->after('bobot_harian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilais', function (Blueprint $table) {
            $table->dropColumn([
                'sumatif_1',
                'sumatif_2',
                'sumatif_3',
                'sumatif_4',
                'sumatif_5',
                'sumatif_6',
                'rata_sumatif_harian'
            ]);
        });

        Schema::table('guru_mapel_kelas', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_sumatif',
                'bobot_harian',
                'bobot_sas'
            ]);
        });
    }
};
