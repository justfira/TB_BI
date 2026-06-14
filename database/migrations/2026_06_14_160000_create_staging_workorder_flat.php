<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staging_workorder_flat')) {
            return;
        }

        Schema::create('staging_workorder_flat', function (Blueprint $table) {
            $table->id();
            $table->string('wo_sc_id', 100)->nullable()->index();
            $table->string('sc_id', 100)->nullable();
            $table->string('track_id', 255)->nullable()->index();
            $table->string('track_id_baru', 255)->nullable();
            $table->date('tanggal')->nullable()->index();
            $table->date('tanggal_order')->nullable();
            $table->date('tanggal_komitmen')->nullable();
            $table->date('tgl_input_hd_gdocs')->nullable();
            $table->string('sto', 255)->nullable();
            $table->string('status_wo', 255)->nullable();
            $table->string('status_sc', 100)->nullable();
            $table->text('kendala_pt1')->nullable();
            $table->string('kategori_roc', 100)->nullable();
            $table->string('kategori_solusi', 150)->nullable();
            $table->text('solusi_kendala')->nullable();
            $table->string('nik_teknisi', 100)->nullable();
            $table->string('korlap', 150)->nullable();
            $table->string('komandan_team', 150)->nullable();
            $table->string('mitra', 150)->nullable();
            $table->string('spv', 150)->nullable();
            $table->string('cp', 100)->nullable();
            $table->string('nama_pelanggan', 255)->nullable();
            $table->string('nama_contact', 255)->nullable();
            $table->string('segment', 100)->nullable();
            $table->string('layanan', 150)->nullable();
            $table->text('alamat_instalasi')->nullable();
            $table->string('uic', 100)->nullable();
            $table->string('koordinat_pelanggan', 255)->nullable();
            $table->string('odp', 255)->nullable();
            $table->string('odc', 255)->nullable();
            $table->string('gpon', 255)->nullable();
            $table->string('feeder', 255)->nullable();
            $table->string('distribusi', 255)->nullable();
            $table->string('datek1', 255)->nullable();
            $table->string('datek_inputan', 255)->nullable();
            $table->string('datek_real', 255)->nullable();
            $table->string('hasil_ukur_odp', 255)->nullable();
            $table->string('hasil_ukur_distribusi', 255)->nullable();
            $table->string('hasil_ukur_feeder', 255)->nullable();
            $table->decimal('durasi_hari', 10, 2)->nullable();
            $table->decimal('durasi', 10, 2)->nullable();
            $table->decimal('durasi_manja', 10, 2)->nullable();
            $table->string('durasi_grup', 100)->nullable();
            $table->decimal('durasi_grup_pengerjaan', 8, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->text('keterangan_sm_provisioning')->nullable();
            $table->text('keterangan_tl_provisioning')->nullable();
            $table->string('hasil_solusi_maintenance', 255)->nullable();
            $table->string('hasil_solusi_optima', 255)->nullable();
            $table->string('hasil_solusi_sdi', 255)->nullable();
            $table->integer('total_eskalasi')->nullable();
            $table->integer('jumlah_kendala')->nullable();
            $table->boolean('is_unsc')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_workorder_flat');
    }
};
