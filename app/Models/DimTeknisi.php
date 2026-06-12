<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DimTeknisi extends Model
{
    use HasFactory;

    protected $table = 'dim_teknisi';
    protected $primaryKey = 'teknisi_id';

    protected $fillable = [
        'nik_teknisi',
        'nama_teknisi',
        'nama_mitra',
        'korlap',
        'komandan_team',
        'unit_kerja',
        'status_aktif',
        'spv',
        'cp',
    ];

    public function factWorkorders()
    {
        return $this->hasMany(FactWorkorder::class, 'dim_teknisi_id');
    }

    public function factKendalateknis()
    {
        return $this->hasMany(FactKendalateknis::class, 'dim_teknisi_id');
    }
}
