<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_infrastruktur')) {
            Schema::create('dim_infrastruktur', function (Blueprint $table) {
            $table->id();
            $table->string('odp')->nullable();
            $table->string('odc')->nullable();
            $table->string('gpon')->nullable();

            $table->string('feeder')->nullable();
            $table->string('distribusi')->nullable();
            $table->string('core_distribusi')->nullable();

            $table->string('datek1')->nullable();
            $table->string('datek_inputan')->nullable();
            $table->string('datek_real')->nullable();

            $table->string('hasil_ukur_odp')->nullable();
            $table->string('hasil_ukur_distribusi')->nullable();
            $table->string('hasil_ukur_feeder')->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_infrastruktur');
    }
};