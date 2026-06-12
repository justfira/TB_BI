<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buat tabel etl_logs HANYA jika belum ada
        if (! Schema::hasTable('etl_logs')) {
            Schema::create('etl_logs', function (Blueprint $table) {
                $table->id();
                $table->timestamp('imported_at')->nullable();
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedInteger('duplicate_count')->default(0);
                $table->enum('status', ['running', 'done', 'error'])->default('running');
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        } else {
            // Tabel sudah ada — pastikan kolom-kolom baru ada
            Schema::table('etl_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('etl_logs', 'status')) {
                    $table->enum('status', ['running', 'done', 'error'])->default('running')->after('duplicate_count');
                }
                if (! Schema::hasColumn('etl_logs', 'error_message')) {
                    $table->text('error_message')->nullable()->after('status');
                }
            });
        }

        // Tambah kolom status_group ke dim_status jika belum ada
        if (Schema::hasTable('dim_status') && ! Schema::hasColumn('dim_status', 'status_group')) {
            Schema::table('dim_status', function (Blueprint $table) {
                $table->string('status_group', 30)->nullable()->after('status_final');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('etl_logs');
    }
};