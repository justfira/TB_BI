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
    Schema::table('dim_teknisi', function (Blueprint $table) {
        if (!Schema::hasColumn('dim_teknisi', 'nama_mitra'))
            $table->string('nama_mitra', 150)->nullable();
        if (!Schema::hasColumn('dim_teknisi', 'cp'))
            $table->string('cp', 100)->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dim_teknisi', function (Blueprint $table) {
            //
        });
    }
};
