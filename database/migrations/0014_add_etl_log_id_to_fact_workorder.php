<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fact_workorder', function (Blueprint $table) {
            $table->unsignedBigInteger('etl_log_id')->nullable()->after('wo_id')->index();
        });

        Schema::table('dim_infrastruktur', function (Blueprint $table) {
            $table->unsignedBigInteger('etl_log_id')->nullable()->after('infra_id')->index();
        });

        Schema::table('fact_kendalateknis', function (Blueprint $table) {
            $table->unsignedBigInteger('etl_log_id')->nullable()->after('kendala_teknis_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('fact_workorder', function (Blueprint $table) {
            $table->dropColumn('etl_log_id');
        });

        Schema::table('dim_infrastruktur', function (Blueprint $table) {
            $table->dropColumn('etl_log_id');
        });

        Schema::table('fact_kendalateknis', function (Blueprint $table) {
            $table->dropColumn('etl_log_id');
        });
    }
};