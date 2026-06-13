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

                $table->string('k_contact', 255)->nullable()->unique();

                $table->string('nama_pelanggan', 255)->nullable();

                $table->string('cp', 100)->nullable();

                $table->string('segment', 100)->nullable();

                $table->string('layanan', 150)->nullable();

                $table->string('uic', 100)->nullable();

                $table->text('alamat_instalasi')->nullable();

                $table->string('koordinat_pelanggan', 255)->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_pelanggan');
    }
};