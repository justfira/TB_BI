<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('etl_logs')) {
            Schema::create('etl_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('total_rows')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('duplicate_count')->default(0);
            $table->decimal('duration_seconds', 10, 2)->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('etl_logs');
    }
};