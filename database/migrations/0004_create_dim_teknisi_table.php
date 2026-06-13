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

                $table->string('nik_teknisi', 100)->nullable()->unique();

                $table->string('nama_teknisi', 191)->nullable();

                $table->string('korlap', 150)->nullable();

                $table->string('komandan_team', 150)->nullable();

                $table->string('mitra', 150)->nullable();

                $table->string('spv', 150)->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_teknisi');
    }
};