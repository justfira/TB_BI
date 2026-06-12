<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_pelanggan')) {
            Schema::create('dim_pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pelanggan', 191);
            $table->string('kode_pelanggan', 100)->nullable()->unique();
            $table->string('tipe_pelanggan', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_pelanggan');
    }
};