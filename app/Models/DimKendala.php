<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DimKendala extends Model
{
    use HasFactory;

    protected $table = 'dim_kendala';
    protected $primaryKey = 'kendala_id';

    protected $fillable = [
        'kendala_pt1',
        'kategori_roc',
        'kategori_solusi',
        'solusi_kendala',
        'keterangan',
    ];

    public function factWorkorders()
    {
        return $this->hasMany(FactWorkorder::class, 'dim_kendala_id');
    }

    public function factKendalateknis()
    {
        return $this->hasMany(FactKendalateknis::class, 'dim_kendala_id');
    }
}
