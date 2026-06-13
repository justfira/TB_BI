<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model FactWorkorder
 *
 * Kolom DB yang relevan (sesuai skema):
 *   id, dim_waktu_id, dim_sto_id, dim_teknisi_id, dim_pelanggan_id, dim_kendala_id, dim_status_id
 *   tanggal_order, tanggal_komitmen, status_wo
 *   durasi_hari, durasi_pengerjaan_menit, durasi_grup, durasi_manja
 *   is_sla_tercapai (tinyint),  is_workfail (tinyint)
 *   tgl_input_hd_gdocs, sc_id, track_id, track_id_baru
 */
class FactWorkorder extends Model
{
    protected $table = 'fact_workorder';

    protected $casts = [
        'tanggal_order'      => 'date',
        'tanggal_komitmen'   => 'date',
        'tgl_input_hd_gdocs' => 'datetime',
        'is_sla_tercapai'    => 'boolean',
        'is_workfail'        => 'boolean',
        'durasi_pengerjaan_menit' => 'decimal:2',
    ];

    // ─── Relasi ────────────────────────────────────────────────────────────────

    public function waktu(): BelongsTo
    {
        return $this->belongsTo(DimWaktu::class, 'dim_waktu_id', 'id');
    }

    public function sto(): BelongsTo
    {
        return $this->belongsTo(DimSto::class, 'dim_sto_id', 'id');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(DimTeknisi::class, 'dim_teknisi_id', 'id');
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(DimPelanggan::class, 'dim_pelanggan_id', 'id');
    }

    public function kendala(): BelongsTo
    {
        return $this->belongsTo(DimKendala::class, 'dim_kendala_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DimStatus::class, 'dim_status_id', 'id');
    }

    public function kendalaTeknis(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FactKendalateknis::class, 'wo_id', 'wo_id');
    }

    // ─── Accessor kenyamanan ───────────────────────────────────────────────────

    /**
     * Durasi dalam jam (dari menit).
     */
    public function getDurasiJamAttribute(): float
    {
        return round(($this->durasi_pengerjaan_menit ?? 0) / 60, 2);
    }
}
