<?php

namespace App\Models\Spatial;

use Illuminate\Database\Eloquent\Model;

class PatokROWPoint extends Model
{
    protected $table = 'spatial_patok_row_point';
    protected $fillable = [
        'jalan_tol_id',
        'geom',
        'layer',
    ];

    public function jalanTol()
    {
        return $this->belongsTo(\App\Models\JalanTol::class, 'jalan_tol_id');
    }
}