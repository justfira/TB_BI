<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_infrastruktur')) {
            Schema::create('dim_infrastruktur', function (Blueprint $table) {
            $table->id();
            $table->string('tipe_infrastruktur', 100);
            $table->string('vendor', 100)->nullable();
            $table->string('lokasi', 191)->nullable();
            $table->string('nama_perangkat', 150)->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_infrastruktur');
    }
};