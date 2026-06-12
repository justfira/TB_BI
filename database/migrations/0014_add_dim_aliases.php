<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add alias dim_ columns to fact_workorder
        Schema::table('fact_workorder', function (Blueprint $table) {
            if (! Schema::hasColumn('fact_workorder', 'dim_waktu_id')) {
                $table->unsignedBigInteger('dim_waktu_id')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'dim_sto_id')) {
                $table->unsignedBigInteger('dim_sto_id')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'dim_teknisi_id')) {
                $table->unsignedBigInteger('dim_teknisi_id')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'dim_pelanggan_id')) {
                $table->unsignedBigInteger('dim_pelanggan_id')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'dim_kendala_id')) {
                $table->unsignedBigInteger('dim_kendala_id')->nullable();
            }
            if (! Schema::hasColumn('fact_workorder', 'dim_status_id')) {
                $table->unsignedBigInteger('dim_status_id')->nullable();
            }
        });

        // Populate alias columns from existing legacy columns
        if (Schema::hasColumn('fact_workorder', 'date_id')) {
            DB::statement('UPDATE fact_workorder SET dim_waktu_id = date_id WHERE dim_waktu_id IS NULL');
        }
        if (Schema::hasColumn('fact_workorder', 'sto_id')) {
            DB::statement('UPDATE fact_workorder SET dim_sto_id = sto_id WHERE dim_sto_id IS NULL');
        }
        if (Schema::hasColumn('fact_workorder', 'teknisi_id')) {
            DB::statement('UPDATE fact_workorder SET dim_teknisi_id = teknisi_id WHERE dim_teknisi_id IS NULL');
        }
        if (Schema::hasColumn('fact_workorder', 'pelanggan_id')) {
            DB::statement('UPDATE fact_workorder SET dim_pelanggan_id = pelanggan_id WHERE dim_pelanggan_id IS NULL');
        }
        if (Schema::hasColumn('fact_workorder', 'kendala_id')) {
            DB::statement('UPDATE fact_workorder SET dim_kendala_id = kendala_id WHERE dim_kendala_id IS NULL');
        }
        if (Schema::hasColumn('fact_workorder', 'status_id')) {
            DB::statement('UPDATE fact_workorder SET dim_status_id = status_id WHERE dim_status_id IS NULL');
        }

        // Add alias dim_ columns to fact_kendalateknis
        Schema::table('fact_kendalateknis', function (Blueprint $table) {
            if (! Schema::hasColumn('fact_kendalateknis', 'dim_kendala_id')) {
                $table->unsignedBigInteger('dim_kendala_id')->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'dim_teknisi_id')) {
                $table->unsignedBigInteger('dim_teknisi_id')->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'dim_status_id')) {
                $table->unsignedBigInteger('dim_status_id')->nullable();
            }
            if (! Schema::hasColumn('fact_kendalateknis', 'dim_infrastruktur_id')) {
                $table->unsignedBigInteger('dim_infrastruktur_id')->nullable();
            }
        });

        // Populate fact_kendalateknis alias columns. For teknisi/status, derive from fact_workorder via wo_id if present.
        if (Schema::hasColumn('fact_kendalateknis', 'wo_id') && Schema::hasColumn('fact_workorder', 'wo_id')) {
            DB::statement('UPDATE fact_kendalateknis fk JOIN fact_workorder fw ON fk.wo_id = fw.wo_id SET fk.dim_teknisi_id = fw.teknisi_id WHERE fk.dim_teknisi_id IS NULL');
            DB::statement('UPDATE fact_kendalateknis fk JOIN fact_workorder fw ON fk.wo_id = fw.wo_id SET fk.dim_status_id = fw.status_id WHERE fk.dim_status_id IS NULL');
        }
        if (Schema::hasColumn('fact_kendalateknis', 'kendala_id')) {
            DB::statement('UPDATE fact_kendalateknis SET dim_kendala_id = kendala_id WHERE dim_kendala_id IS NULL');
        }
        if (Schema::hasColumn('fact_kendalateknis', 'infra_id')) {
            DB::statement('UPDATE fact_kendalateknis SET dim_infrastruktur_id = infra_id WHERE dim_infrastruktur_id IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('fact_workorder', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('fact_workorder', 'dim_waktu_id')) $drop[] = 'dim_waktu_id';
            if (Schema::hasColumn('fact_workorder', 'dim_sto_id')) $drop[] = 'dim_sto_id';
            if (Schema::hasColumn('fact_workorder', 'dim_teknisi_id')) $drop[] = 'dim_teknisi_id';
            if (Schema::hasColumn('fact_workorder', 'dim_pelanggan_id')) $drop[] = 'dim_pelanggan_id';
            if (Schema::hasColumn('fact_workorder', 'dim_kendala_id')) $drop[] = 'dim_kendala_id';
            if (Schema::hasColumn('fact_workorder', 'dim_status_id')) $drop[] = 'dim_status_id';
            if (! empty($drop)) $table->dropColumn($drop);
        });

        Schema::table('fact_kendalateknis', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('fact_kendalateknis', 'dim_kendala_id')) $drop[] = 'dim_kendala_id';
            if (Schema::hasColumn('fact_kendalateknis', 'dim_teknisi_id')) $drop[] = 'dim_teknisi_id';
            if (Schema::hasColumn('fact_kendalateknis', 'dim_status_id')) $drop[] = 'dim_status_id';
            if (Schema::hasColumn('fact_kendalateknis', 'dim_infrastruktur_id')) $drop[] = 'dim_infrastruktur_id';
            if (! empty($drop)) $table->dropColumn($drop);
        });
    }
};
