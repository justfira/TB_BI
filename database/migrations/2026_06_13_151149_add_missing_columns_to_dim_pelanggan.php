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
    Schema::table('dim_pelanggan', function (Blueprint $table) {
        if (!Schema::hasColumn('dim_pelanggan', 'nama_pelanggan'))
            $table->string('nama_pelanggan', 191)->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'nama_contact'))
            $table->string('nama_contact', 191)->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'segment'))
            $table->string('segment', 100)->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'layanan'))
            $table->string('layanan', 100)->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'alamat_instalasi'))
            $table->text('alamat_instalasi')->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'uic'))
            $table->string('uic', 100)->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'koordinat_lat'))
            $table->decimal('koordinat_lat', 10, 7)->nullable();
        if (!Schema::hasColumn('dim_pelanggan', 'koordinat_lon'))
            $table->decimal('koordinat_lon', 10, 7)->nullable();
    });
}

public function down()
{
    Schema::table('dim_pelanggan', function (Blueprint $table) {
        $table->dropColumn([
            'nama_pelanggan', 'nama_contact', 'segment',
            'layanan', 'alamat_instalasi', 'uic',
            'koordinat_lat', 'koordinat_lon',
        ]);
    });
}
};
