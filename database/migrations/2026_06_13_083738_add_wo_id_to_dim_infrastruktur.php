<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_infrastruktur', function (Blueprint $table) {
            // dipakai untuk mapping infra_id ↔ wo_id biar ETL tidak gagal/miss
            if (!Schema::hasColumn('dim_infrastruktur', 'wo_id')) {
                $table->string('wo_id', 100)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dim_infrastruktur', function (Blueprint $table) {
            if (Schema::hasColumn('dim_infrastruktur', 'wo_id')) {
                $table->dropIndex(['wo_id']);
                $table->dropColumn('wo_id');
            }
        });
    }
};

