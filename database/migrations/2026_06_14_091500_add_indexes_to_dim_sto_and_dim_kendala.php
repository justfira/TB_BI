<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dim_sto') || ! Schema::hasTable('dim_kendala')) {
            return;
        }

        if (! $this->indexExists('dim_sto', 'idx_dim_sto_nama_sto')) {
            Schema::table('dim_sto', function (Blueprint $table) {
                $table->index('nama_sto', 'idx_dim_sto_nama_sto');
            });
        }

        if (! $this->indexExists('dim_kendala', 'idx_dim_kendala_kendala_pt1')) {
            if (DB::getDriverName() === 'mysql') {
                // kendala_pt1 = varchar(150), jadi index full tanpa prefix sudah aman
                DB::statement('CREATE INDEX idx_dim_kendala_kendala_pt1 ON dim_kendala (kendala_pt1)');
            } else {
                Schema::table('dim_kendala', function (Blueprint $table) {
                    $table->index('kendala_pt1', 'idx_dim_kendala_kendala_pt1');
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dim_sto') || ! Schema::hasTable('dim_kendala')) {
            return;
        }

        if ($this->indexExists('dim_sto', 'idx_dim_sto_nama_sto')) {
            Schema::table('dim_sto', function (Blueprint $table) {
                $table->dropIndex('idx_dim_sto_nama_sto');
            });
        }

        if ($this->indexExists('dim_kendala', 'idx_dim_kendala_kendala_pt1')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('DROP INDEX idx_dim_kendala_kendala_pt1 ON dim_kendala');
            } else {
                Schema::table('dim_kendala', function (Blueprint $table) {
                    $table->dropIndex('idx_dim_kendala_kendala_pt1');
                });
            }
        }
    }

    /**
     * Cek apakah index dengan nama tertentu sudah ada pada tabel.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SELECT COUNT(1) as cnt FROM information_schema.statistics 
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return ((int) $result[0]->cnt) > 0;
    }
};