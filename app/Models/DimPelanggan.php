<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DimPelanggan extends Model
{
    use HasFactory;

    protected $table = 'dim_pelanggan';
    protected $primaryKey = 'pelanggan_id';

    protected $fillable = [
        'nama_pelanggan',
        'kode_tracking',
        'uic',
        'tipe_pelanggan',
        'alamat_instalasi',
        'nama_contact',
        'layanan',
        'segment',
        'koordinat_lat',
        'koordinat_lon',
    ];

    public function factWorkorders()
    {
        return $this->hasMany(FactWorkorder::class, 'dim_pelanggan_id');
    }
}
