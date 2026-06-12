<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DimInfrastruktur extends Model
{
    use HasFactory;

    protected $table = 'dim_infrastruktur';
    protected $primaryKey = 'infra_id';

    protected $fillable = [
        'tipe_infrastruktur',
        'odp',
        'odc',
        'gpon',
        'feeder',
        'distribusi',
        'datek1',
        'datek_inputan',
        'datek_real',
        'base_tray_odc',
        'port_base_tray_odc',
        'hasil_ukur_odp',
        'hasil_ukur_distribusi',
        'hasil_ukur_feeder',
        'vendor',
        'lokasi',
        'nama_perangkat',
    ];

    public function factWorkorders()
    {
        return $this->hasMany(FactWorkorder::class, 'dim_infrastruktur_id');
    }
}
