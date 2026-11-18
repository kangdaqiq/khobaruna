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
            $table->string('tahun_ajaran', 20)->default('2025/2026')->after('name');
            $table->string('semester', 10)->default('2')->after('tahun_ajaran'); // '1' / '2' atau 'Ganjil' / 'Genap'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['tahun_ajaran', 'semester']);
        });
    }
};
