<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model FactWorkorder
 *
 * Kolom DB yang relevan (sesuai skema):
 *   wo_id, date_id, sto_id, teknisi_id, pelanggan_id, kendala_id, status_id
 *   tanggal_order, tanggal_komitmen, status_wo
 *   durasi_hari, durasi_pengerjaan_menit, durasi_grup, durasi_manja
 *   is_sla_tercapai (tinyint),  is_workfail (tinyint)
 *   tgl_input_hd_gdocs, sc_id, track_id, track_id_baru
 */
class FactWorkorder extends Model
{
    protected $table = 'fact_workorder';
    protected $primaryKey = 'wo_id';

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
        return $this->belongsTo(DimWaktu::class, 'date_id', 'date_id');
    }

    public function sto(): BelongsTo
    {
        return $this->belongsTo(DimSto::class, 'sto_id', 'sto_id');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(DimTeknisi::class, 'teknisi_id', 'teknisi_id');
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(DimPelanggan::class, 'pelanggan_id', 'pelanggan_id');
    }

    public function kendala(): BelongsTo
    {
        return $this->belongsTo(DimKendala::class, 'kendala_id', 'kendala_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DimStatus::class, 'status_id', 'status_id');
    }

    public function infrastruktur(): BelongsTo
    {
        return $this->belongsTo(DimInfrastruktur::class, 'wo_sc_id', 'wo_id');
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
