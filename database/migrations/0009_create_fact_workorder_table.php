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

                // DIMENSI

                $table->foreignId('dim_waktu_id')
                    ->constrained('dim_waktu')
                    ->cascadeOnDelete();

                $table->foreignId('dim_sto_id')
                    ->constrained('dim_sto')
                    ->cascadeOnDelete();

                $table->foreignId('dim_teknisi_id')
                    ->constrained('dim_teknisi')
                    ->cascadeOnDelete();

                $table->foreignId('dim_pelanggan_id')
                    ->constrained('dim_pelanggan')
                    ->cascadeOnDelete();

                $table->foreignId('dim_kendala_id')
                    ->constrained('dim_kendala')
                    ->cascadeOnDelete();

                $table->foreignId('dim_infrastruktur_id')
                    ->constrained('dim_infrastruktur')
                    ->cascadeOnDelete();

                $table->foreignId('dim_status_id')
                    ->constrained('dim_status')
                    ->cascadeOnDelete();

                // IDENTITAS WORK ORDER

                $table->string('wo_sc_id', 100)->index();

                $table->string('sc_id', 100)->nullable();

                $table->string('track_id', 255)->nullable();

                $table->string('track_id_baru', 255)->nullable();

                // TANGGAL

                $table->date('tanggal_order')->nullable();

                $table->date('tanggal_komitmen')->nullable();

                $table->date('tgl_input_hd_gdocs')->nullable();

                // STATUS

                $table->string('status_wo', 100)->nullable();

                $table->string('status_sc', 100)->nullable();

                // DURASI

                $table->decimal('durasi_hari', 10, 2)->nullable();

                $table->decimal('durasi', 10, 2)->nullable();

                $table->decimal('durasi_manja', 10, 2)->nullable();

                $table->decimal('durasi_pengerjaan_kendala', 10, 2)->nullable();

                $table->string('durasi_grup', 100)->nullable();

                $table->string('durasi_grup_kendala', 100)->nullable();

                // KPI BI

                $table->boolean('is_sla_tercapai')->default(false);

                $table->boolean('is_workfail')->default(false);

                // KETERANGAN

                $table->text('keterangan')->nullable();

                $table->text('keterangan_sm_provisioning')->nullable();

                $table->text('keterangan_tl_provisioning')->nullable();

                $table->timestamps();

                $table->unique([
                    'wo_sc_id',
                    'track_id'
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_workorder');
    }
};