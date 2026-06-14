<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_status', function (Blueprint $table) {
            if (!Schema::hasColumn('dim_status', 'status_wo')) {
                $table->string('status_wo', 100)->nullable()->after('status_id');
            }
            if (!Schema::hasColumn('dim_status', 'status_final')) {
                $table->string('status_final', 100)->nullable()->after('status_wo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dim_status', function (Blueprint $table) {
            $table->dropColumn(['status_wo', 'status_final']);
        });
    }
};