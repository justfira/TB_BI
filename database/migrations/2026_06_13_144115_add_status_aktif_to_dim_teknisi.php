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
            if (! Schema::hasColumn('dim_teknisi', 'status_aktif')) {
                $table->boolean('status_aktif')->default(1)->after('spv');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dim_teknisi', function (Blueprint $table) {
            if (Schema::hasColumn('dim_teknisi', 'status_aktif')) {
                $table->dropColumn('status_aktif');
            }
        });
    }
};
