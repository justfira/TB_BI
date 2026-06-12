<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactKendalateknis extends Model
{
    use HasFactory;

    protected $table = 'fact_kendalateknis';
    protected $primaryKey = 'kendala_teknis_id';

    protected $fillable = [
        'wo_id',
        'date_id',
        'sto_id',
        'dim_kendala_id',
        'kendala_id',
        'dim_teknisi_id',
        'dim_status_id',
        'infra_id',
        'dim_infrastruktur_id',
        'keterangan',
        'resolusi_jam',
        'durasi_grup_pengerjaan',
        'hasil_solusi_maintenance',
        'hasil_solusi_optima',
        'hasil_solusi_sdi',
        'total_eskalasi',
        'jumlah_kendala',
        'root_cause',
    ];

    public function workorder()
    {
        return $this->belongsTo(FactWorkorder::class, 'wo_id', 'wo_id');
    }

    public function kendala()
    {
        return $this->belongsTo(DimKendala::class, 'dim_kendala_id');
    }

    public function teknisi()
    {
        return $this->belongsTo(DimTeknisi::class, 'dim_teknisi_id');
    }

    public function status()
    {
        return $this->belongsTo(DimStatus::class, 'dim_status_id');
    }
}
