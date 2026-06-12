<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_sto')) {
            Schema::create('dim_sto', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sto', 50)->unique();
            $table->string('nama_sto', 191);
            $table->string('wilayah', 100)->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('cluster', 100)->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_sto');
    }
};