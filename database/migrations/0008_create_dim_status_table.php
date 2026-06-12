<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_status')) {
            Schema::create('dim_status', function (Blueprint $table) {
            $table->id();
            $table->string('status_name', 100)->unique();
            $table->string('status_group', 100)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_status');
    }
};