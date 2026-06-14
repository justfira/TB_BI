<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasColumn('dim_pelanggan', 'kode_tracking')) {
            Schema::table('dim_pelanggan', function (Blueprint $table) {
                $table->string('kode_tracking')->nullable()->unique();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('dim_pelanggan', 'kode_tracking')) {
            Schema::table('dim_pelanggan', function (Blueprint $table) {
                $table->dropColumn('kode_tracking');
            });
        }
    }
};