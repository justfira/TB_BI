<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DimWaktu extends Model
{
    use HasFactory;

    protected $table = 'dim_waktu';
    protected $primaryKey = 'date_id';

    protected $fillable = [
        'tanggal',
        'tahun',
        'bulan',
        'hari',
        'nama_bulan',
        'nama_hari',
        'kuartal',
        'hari_kerja',
    ];

    public function factWorkorders()
    {
        return $this->hasMany(FactWorkorder::class, 'dim_waktu_id');
    }
}
