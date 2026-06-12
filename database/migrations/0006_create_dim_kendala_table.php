<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_kendala')) {
            Schema::create('dim_kendala', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kendala', 100)->nullable()->unique();
            $table->string('nama_kendala', 191);
            $table->string('kategori', 100)->nullable();
            $table->string('sub_kategori', 100)->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_kendala');
    }
};