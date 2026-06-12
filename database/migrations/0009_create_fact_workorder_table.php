<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fact_workorder')) {
            Schema::create('fact_workorder', function (Blueprint $table) {
            $table->id();
            $table->string('wo_id', 100)->index();
            $table->string('sc_id', 100)->nullable()->index();
            $table->foreignId('dim_waktu_id')->constrained('dim_waktu')->cascadeOnDelete();
            $table->foreignId('dim_sto_id')->constrained('dim_sto')->cascadeOnDelete();
            $table->foreignId('dim_teknisi_id')->constrained('dim_teknisi')->cascadeOnDelete();
            $table->foreignId('dim_pelanggan_id')->constrained('dim_pelanggan')->cascadeOnDelete();
            $table->foreignId('dim_kendala_id')->constrained('dim_kendala')->cascadeOnDelete();
            $table->foreignId('dim_infrastruktur_id')->constrained('dim_infrastruktur')->cascadeOnDelete();
            $table->foreignId('dim_status_id')->constrained('dim_status')->cascadeOnDelete();
            $table->string('uraian_kendala', 500)->nullable();
            $table->decimal('durasi_jam', 8, 2)->nullable();
            $table->decimal('durasi_hari', 8, 2)->nullable();
            $table->decimal('sla_achievement', 5, 2)->nullable();
            $table->boolean('workfail_flag')->default(false);
            $table->string('standar_status', 100)->nullable();
            $table->string('mitra', 150)->nullable();
            $table->timestamps();
            $table->unique(['wo_id', 'sc_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_workorder');
    }
};