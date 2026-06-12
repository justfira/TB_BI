<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_waktu')) {
            Schema::create('dim_waktu', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->smallInteger('tahun')->index();
            $table->tinyInteger('bulan')->index();
            $table->tinyInteger('hari')->index();
            $table->string('nama_bulan', 20);
            $table->string('nama_hari', 20);
            $table->tinyInteger('kuartal')->index();
            $table->boolean('hari_kerja')->default(true);
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_waktu');
    }
};