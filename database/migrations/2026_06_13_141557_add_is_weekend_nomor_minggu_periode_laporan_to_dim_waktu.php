<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_waktu', function (Blueprint $table) {
            if (!Schema::hasColumn('dim_waktu', 'is_weekend')) {
                $table->boolean('is_weekend')->default(false)->after('kuartal');
            }
            if (!Schema::hasColumn('dim_waktu', 'nomor_minggu')) {
                $table->tinyInteger('nomor_minggu')->nullable()->after('kuartal');
            }
            if (!Schema::hasColumn('dim_waktu', 'periode_laporan')) {
                $table->string('periode_laporan', 20)->nullable()->after('nama_hari');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dim_waktu', function (Blueprint $table) {
            $table->dropColumn(['is_weekend', 'nomor_minggu', 'periode_laporan']);
        });
    }
};