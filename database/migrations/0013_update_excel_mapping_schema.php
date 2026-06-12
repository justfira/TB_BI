<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_sto', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_sto', 'sto_input')) {
                $table->string('sto_input', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_sto', 'branch')) {
                $table->string('branch', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_sto', 'sektor')) {
                $table->string('sektor', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_sto', 'hsa')) {
                $table->string('hsa', 100)->nullable();
            }
        });

        Schema::table('dim_teknisi', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_teknisi', 'nama_mitra')) {
                $table->string('nama_mitra', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_teknisi', 'korlap')) {
                $table->string('korlap', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_teknisi', 'komandan_team')) {
                $table->string('komandan_team', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_teknisi', 'spv')) {
                $table->string('spv', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_teknisi', 'cp')) {
                $table->string('cp', 150)->nullable();
            }
        });

        Schema::table('dim_pelanggan', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_pelanggan', 'nama_contact')) {
                $table->string('nama_contact', 191)->nullable();
            }
            if (! Schema::hasColumn('dim_pelanggan', 'layanan')) {
                $table->string('layanan', 191)->nullable();
            }
            if (! Schema::hasColumn('dim_pelanggan', 'alamat_instalasi')) {
                $table->string('alamat_instalasi', 255)->nullable();
            }
            if (! Schema::hasColumn('dim_pelanggan', 'koordinat_lat')) {
                $table->decimal('koordinat_lat', 12, 8)->nullable();
            }
            if (! Schema::hasColumn('dim_pelanggan', 'koordinat_lon')) {
                $table->decimal('koordinat_lon', 12, 8)->nullable();
            }
        });

        Schema::table('dim_kendala', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_kendala', 'kategori_roc')) {
                $table->string('kategori_roc', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_kendala', 'kategori_solusi')) {
                $table->string('kategori_solusi', 100)->nullable();
            }
            if (! Schema::hasColumn('dim_kendala', 'keterangan')) {
                $table->string('keterangan', 500)->nullable();
            }
        });

        Schema::table('dim_infrastruktur', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_infrastruktur', 'odp')) {
                $table->string('odp', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'odc')) {
                $table->string('odc', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'gpon')) {
                $table->string('gpon', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'feeder')) {
                $table->string('feeder', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'distribusi')) {
                $table->string('distribusi', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'datek1')) {
                $table->string('datek1', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'datek_inputan')) {
                $table->string('datek_inputan', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'datek_real')) {
                $table->string('datek_real', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'base_tray_odc')) {
                $table->string('base_tray_odc', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'port_base_tray_odc')) {
                $table->string('port_base_tray_odc', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'hasil_ukur_odp')) {
                $table->string('hasil_ukur_odp', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'hasil_ukur_distribusi')) {
                $table->string('hasil_ukur_distribusi', 150)->nullable();
            }
            if (! Schema::hasColumn('dim_infrastruktur', 'hasil_ukur_feeder')) {
                $table->string('hasil_ukur_feeder', 150)->nullable();
            }
        });

        Schema::table('dim_status', function (Blueprint $table) {
            if (! Schema::hasColumn('dim_status', 'status_sc')) {
                $table->string('status_sc', 100)->nullable();
            }
        });

        Schema::table('fact_workorder', function (Blueprint $table) {
            if (! Schema::hasColumn('fact_workorder', 'tanggal_order')) {
                $table->date('tanggal_order')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'tanggal_komitmen')) {
                $table->date('tanggal_komitmen')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'track_id')) {
                $table->string('track_id', 150)->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'status_wo')) {
                $table->string('status_wo', 100)->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'durasi_pengerjaan_menit')) {
                $table->decimal('durasi_pengerjaan_menit', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'durasi_manja')) {
                $table->decimal('durasi_manja', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'durasi_grup')) {
                $table->decimal('durasi_grup', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'tgl_input_hd_gdocs')) {
                $table->date('tgl_input_hd_gdocs')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'is_unsc')) {
                $table->boolean('is_unsc')->default(false);
            }
        });

        Schema::table('fact_kendalateknis', function (Blueprint $table) {
            if (! Schema::hasColumn('fact_kendalateknis', 'durasi_grup_pengerjaan')) {
                $table->decimal('durasi_grup_pengerjaan', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'hasil_solusi_maintenance')) {
                $table->string('hasil_solusi_maintenance', 255)->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'hasil_solusi_optima')) {
                $table->string('hasil_solusi_optima', 255)->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'hasil_solusi_sdi')) {
                $table->string('hasil_solusi_sdi', 255)->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'total_eskalasi')) {
                $table->integer('total_eskalasi')->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'jumlah_kendala')) {
                $table->integer('jumlah_kendala')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dim_sto', function (Blueprint $table) {
            $table->dropColumn(['sto_input', 'branch', 'sektor', 'hsa']);
        });

        Schema::table('dim_teknisi', function (Blueprint $table) {
            $table->dropColumn(['nama_mitra', 'korlap', 'komandan_team', 'spv', 'cp']);
        });

        Schema::table('dim_pelanggan', function (Blueprint $table) {
            $table->dropColumn(['nama_contact', 'layanan', 'alamat_instalasi', 'koordinat_lat', 'koordinat_lon']);
        });

        Schema::table('dim_kendala', function (Blueprint $table) {
            $table->dropColumn(['kategori_roc', 'kategori_solusi', 'keterangan']);
        });

        Schema::table('dim_infrastruktur', function (Blueprint $table) {
            $table->dropColumn([
                'odp',
                'odc',
                'gpon',
                'feeder',
                'distribusi',
                'datek1',
                'datek_inputan',
                'datek_real',
                'base_tray_odc',
                'port_base_tray_odc',
                'hasil_ukur_odp',
                'hasil_ukur_distribusi',
                'hasil_ukur_feeder',
            ]);
        });

        Schema::table('dim_status', function (Blueprint $table) {
            $table->dropColumn('status_sc');
        });

        Schema::table('fact_workorder', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_order',
                'tanggal_komitmen',
                'track_id',
                'status_wo',
                'durasi_pengerjaan_menit',
                'durasi_manja',
                'durasi_grup',
                'tgl_input_hd_gdocs',
                'is_unsc',
            ]);
        });

        Schema::table('fact_kendalateknis', function (Blueprint $table) {
            $table->dropColumn([
                'durasi_grup_pengerjaan',
                'hasil_solusi_maintenance',
                'hasil_solusi_optima',
                'hasil_solusi_sdi',
                'total_eskalasi',
                'jumlah_kendala',
            ]);
        });
    }
};
