<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kolom DB: status_id, status_wo, status_sc, kategori_status, status_final, status_group
 *
 * status_group diisi saat ETL:
 *   - 'Selesai'  → status_final mengandung kata selesai / close / done
 *   - 'Pending'  → status_final mengandung pending / open / proses
 *   - 'Gagal'    → workfail / cancel / reject
 */
class DimStatus extends Model
{
    protected $table = 'dim_status';
    protected $primaryKey = 'status_id';

    protected $fillable = [
        'status_wo',
        'status_sc',
        'status_name',
        'status_group',
        'status_final',
        'kategori_status',
        'aktif',
    ];

    // Nilai status_group yang dipakai di seluruh aplikasi
    public const GROUP_SELESAI = 'Selesai';
    public const GROUP_PENDING = 'Pending';
    public const GROUP_GAGAL   = 'Gagal';

    /**
     * Tentukan status_group dari status_wo / status_final secara otomatis.
     */
    public static function resolveGroup(string $statusWo): string
    {
        $lower = strtolower($statusWo);

        if (str_contains($lower, 'selesai') || str_contains($lower, 'close') || str_contains($lower, 'done') || str_contains($lower, 'completed')) {
            return self::GROUP_SELESAI;
        }

        if (str_contains($lower, 'gagal') || str_contains($lower, 'cancel') || str_contains($lower, 'reject') || str_contains($lower, 'workfail')) {
            return self::GROUP_GAGAL;
        }

        return self::GROUP_PENDING;
    }
}