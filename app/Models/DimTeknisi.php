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
        return $this->hasMany(FactWorkorder::class, 'teknisi_id', 'teknisi_id');
    }

    public function factKendalateknis()
    {
        return $this->hasMany(FactKendalateknis::class, 'dim_teknisi_id');
    }

    /**
     * Get clean technician name from nik_teknisi if nama_teknisi is empty.
     */
    public function getNamaTeknisiFormattedAttribute(): string
    {
        if ($this->nama_teknisi && trim($this->nama_teknisi) !== '' && trim($this->nama_teknisi) !== '-') {
            return $this->nama_teknisi;
        }
        if (str_contains($this->nik_teknisi, ' - ')) {
            return explode(' - ', $this->nik_teknisi, 2)[1];
        }
        return $this->nik_teknisi ?? '-';
    }
}

