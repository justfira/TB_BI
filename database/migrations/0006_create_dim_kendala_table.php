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

                $table->string('kategori_solusi', 150)->nullable();

                $table->string('kategori_roc', 100)->nullable();

                $table->text('kendala_pt1')->nullable();

                $table->text('solusi_kendala')->nullable();

                $table->text('solusi_maintenance')->nullable();

                $table->text('solusi_optima')->nullable();

                $table->text('solusi_sdi_daman')->nullable();

                $table->longText('info_detail')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_kendala');
    }
};