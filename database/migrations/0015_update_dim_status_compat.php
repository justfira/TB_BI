<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_status', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_status', 'status_name')) {
                $table->string('status_name', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_status', 'status_group')) {
                $table->string('status_group', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_status', 'aktif')) {
                $table->boolean('aktif')->default(true);
            }
        });

        if (Schema::hasColumn('dim_status', 'status_wo')) {
            DB::statement('UPDATE dim_status SET status_name = status_wo WHERE status_name IS NULL');
        }

        if (Schema::hasColumn('dim_status', 'status_final') || Schema::hasColumn('dim_status', 'kategori_status')) {
            DB::statement('UPDATE dim_status SET status_group = COALESCE(' .
                (Schema::hasColumn('dim_status', 'status_final') ? 'status_final' : 'NULL') . ', ' .
                (Schema::hasColumn('dim_status', 'kategori_status') ? 'kategori_status' : 'NULL') .
                ') WHERE status_group IS NULL');
        }

        DB::statement('UPDATE dim_status SET aktif = 1 WHERE aktif IS NULL');
    }

    public function down(): void
    {
        Schema::table('dim_status', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('dim_status', 'status_name')) {
                $drop[] = 'status_name';
            }
            if (Schema::hasColumn('dim_status', 'status_group')) {
                $drop[] = 'status_group';
            }
            if (Schema::hasColumn('dim_status', 'aktif')) {
                $drop[] = 'aktif';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
