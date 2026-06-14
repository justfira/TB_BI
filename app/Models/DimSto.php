<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DimSto extends Model
{
    use HasFactory;

    protected $table = 'dim_sto';
    protected $primaryKey = 'sto_id';

    protected $fillable = [
        'nama_sto',
        'sto_input',
        'branch',
        'sektor',
        'hsa',
        'wilayah_sto',
    ];

    public function factWorkorders()
    {
        return $this->hasMany(FactWorkorder::class, 'sto_id', 'sto_id');
    }
}
