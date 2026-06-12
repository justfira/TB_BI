<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_teknisi')) {
            Schema::create('dim_teknisi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_teknisi', 191);
            $table->string('nik', 100)->nullable()->unique();
            $table->string('mitra', 150)->nullable();
            $table->string('unit', 100)->nullable();
            $table->string('role', 100)->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_teknisi');
    }
};