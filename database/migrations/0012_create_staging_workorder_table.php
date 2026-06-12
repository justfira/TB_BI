<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staging_workorder')) {
            Schema::create('staging_workorder', function (Blueprint $table) {
                $table->id();
                $table->json('data_json');
                $table->string('status', 20)->default('pending');
                $table->json('errors')->nullable();
                $table->integer('row_number')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_workorder');
    }
};