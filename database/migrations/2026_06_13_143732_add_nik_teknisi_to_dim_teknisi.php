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
        Schema::table('dim_teknisi', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_teknisi', 'nik_teknisi')) {
                $table->string('nik_teknisi', 100)->nullable()->unique()->after('nama_teknisi');
            }
        });

        // Copy existing values from 'nik' if present
        if (Schema::hasColumn('dim_teknisi', 'nik') && Schema::hasColumn('dim_teknisi', 'nik_teknisi')) {
            DB::table('dim_teknisi')
                ->whereNull('nik_teknisi')
                ->update(['nik_teknisi' => DB::raw('nik')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dim_teknisi', function (Blueprint $table) {
            if (Schema::hasColumn('dim_teknisi', 'nik_teknisi')) {
                $table->dropColumn('nik_teknisi');
            }
        });
    }
};
