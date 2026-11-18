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
        Schema::table('kelases', function (Blueprint $table) {
            $table->unsignedTinyInteger('kkm')->default(75)->after('jumlah_siswa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelases', function (Blueprint $table) {
            $table->dropColumn('kkm');
        });
    }
};
