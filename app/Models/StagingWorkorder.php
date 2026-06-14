<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StagingWorkorder extends Model
{
    use HasFactory;

    protected $table = 'staging_workorder';

    protected $fillable = [
        'data_json',
        'status_etl',
        'errors',
        'row_number',
    ];

    protected $casts = [
        'data_json' => 'array',
        'errors' => 'array',
    ];
}
