<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fact_kendalateknis')) {
            Schema::create('fact_kendalateknis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fact_workorder_id')->constrained('fact_workorder')->cascadeOnDelete();
            $table->foreignId('dim_kendala_id')->constrained('dim_kendala')->cascadeOnDelete();
            $table->foreignId('dim_teknisi_id')->constrained('dim_teknisi')->cascadeOnDelete();
            $table->foreignId('dim_status_id')->constrained('dim_status')->cascadeOnDelete();
            $table->string('keterangan', 500)->nullable();
            $table->decimal('resolusi_jam', 8, 2)->nullable();
            $table->string('root_cause', 255)->nullable();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_kendalateknis');
    }
};