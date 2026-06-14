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
        if (!Schema::hasColumn('dim_kendala', 'kendala_pt1')) {
            Schema::table('dim_kendala', function (Blueprint $table) {
                $table->text('kendala_pt1')->nullable()->after('kategori_roc');
            });
        }

        if (!Schema::hasColumn('dim_kendala', 'solusi_kendala')) {
            Schema::table('dim_kendala', function (Blueprint $table) {
                $table->text('solusi_kendala')->nullable()->after('kendala_pt1');
            });
        }

        if (!Schema::hasColumn('dim_kendala', 'solusi_maintenance')) {
            Schema::table('dim_kendala', function (Blueprint $table) {
                $table->text('solusi_maintenance')->nullable()->after('solusi_kendala');
            });
        }

        if (!Schema::hasColumn('dim_kendala', 'solusi_optima')) {
            Schema::table('dim_kendala', function (Blueprint $table) {
                $table->text('solusi_optima')->nullable()->after('solusi_maintenance');
            });
        }

        if (!Schema::hasColumn('dim_kendala', 'solusi_sdi_daman')) {
            Schema::table('dim_kendala', function (Blueprint $table) {
                $table->text('solusi_sdi_daman')->nullable()->after('solusi_optima');
            });
        }

        if (!Schema::hasColumn('dim_kendala', 'info_detail')) {
            Schema::table('dim_kendala', function (Blueprint $table) {
                $table->longText('info_detail')->nullable()->after('solusi_sdi_daman');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dim_kendala', function (Blueprint $table) {
            $table->dropColumn([
                'kendala_pt1', 'solusi_kendala', 'solusi_maintenance',
                'solusi_optima', 'solusi_sdi_daman', 'info_detail'
            ]);
        });
    }
};
