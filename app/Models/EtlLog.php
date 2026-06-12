<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel etl_logs – catat setiap sesi import.
 *
 * Kolom: id, imported_at, total_rows, success_count, failed_count,
 *        duplicate_count, status (running|done|error), error_message
 */
class EtlLog extends Model
{
    protected $table = 'etl_logs';

    protected $fillable = [
        'imported_at',
        'total_rows',
        'success_count',
        'failed_count',
        'duplicate_count',
        'status',
        'error_message',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    // Persen sukses
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->success_count / $this->total_rows) * 100, 1);
    }
}